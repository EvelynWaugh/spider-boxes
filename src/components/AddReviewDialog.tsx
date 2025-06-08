import React, {useEffect, useState} from "react";
import {useMutation, useQuery, useQueryClient} from "@tanstack/react-query";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/Dialog";
import {Button} from "@/components/ui/Button";
import * as Select from "@radix-ui/react-select";
import {PlusIcon, ChevronDownIcon, CheckIcon} from "@radix-ui/react-icons";
import {useAPI} from "../hooks/useAPI";
import {DynamicField, DynamicFieldRenderer} from "./DynamicFieldRenderer";

interface Product {
  id: number;
  name: string;
  slug: string;
  type: string;
  price: string;
  image: string[] | null;
}

interface AddReviewDialogProps {
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
  productId?: number;
}

interface ReviewFormData {
  product_id: number;
  author_name: string;
  author_email: string;
  author_url?: string;
  content: string;
  rating: number;
  status: "approve" | "hold" | "spam" | "trash";
  verified?: boolean;
  meta: Record<string, any>;
}

export const AddReviewDialog: React.FC<AddReviewDialogProps> = ({
  isOpen,
  onOpenChange,
  productId,
}) => {
  const queryClient = useQueryClient();
  const {get, post, getReviewFields} = useAPI();

  // State for form values
  const [formValues, setFormValues] = useState<Record<string, any>>({});
  const [selectedProductId, setSelectedProductId] = useState<number>(
    productId || 0
  );
  const [errors, setErrors] = useState<Record<string, string>>({});

  // Fetch review fields configuration
  const {data: fieldsData, isLoading: isLoadingFields} = useQuery({
    queryKey: ["review-fields"],
    queryFn: getReviewFields,
    enabled: isOpen,
  });

  const reviewFields: DynamicField[] = fieldsData?.fields
    ? Object.values(fieldsData.fields)
    : []; // Fetch reviews with server-side pagination

  // Fetch products for selection
  const {data: productsData, isLoading: isLoadingProducts} = useQuery({
    queryKey: ["products"],
    queryFn: () => get("/products?per_page=50"),
    enabled: isOpen && !productId, // Only fetch if no specific product is set
  });

  const products: Product[] = productsData?.products || [];

  // Create review mutation
  const createReviewMutation = useMutation({
    mutationFn: (data: ReviewFormData) => post("/reviews", data),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ["reviews"]});
      onOpenChange(false);
      resetForm();
    },
    onError: (error: any) => {
      console.error("Failed to create review:", error);
    },
  });

  // Reset form when dialog opens
  useEffect(() => {
    if (isOpen) {
      resetForm();
      setSelectedProductId(productId || 0);
    }
  }, [isOpen, productId]);

  const resetForm = () => {
    setFormValues({
      author_name: "",
      author_email: "",
      author_url: "",
      content: "",
      rating: 5,
      status: "approve",
      verified: false,
      meta: {},
    });
    setErrors({});
  };
  const handleFieldChange = (
    fieldId: string,
    isMeta: boolean | undefined,
    value: any
  ) => {
    setFormValues((prev) => ({
      ...prev,
      [fieldId]: value,
    }));

    // Clear error when field is changed
    if (errors[fieldId]) {
      setErrors((prev) => ({
        ...prev,
        [fieldId]: "",
      }));
    }
  };

  const validateForm = (): boolean => {
    const newErrors: Record<string, string> = {};

    // Validate product selection (if not pre-selected)
    if (!productId && !selectedProductId) {
      newErrors.product_id = "Product is required";
    }

    // Validate required fields
    if (!formValues.author_name?.trim()) {
      newErrors.author_name = "Reviewer name is required";
    }

    if (!formValues.author_email?.trim()) {
      newErrors.author_email = "Email is required";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formValues.author_email)) {
      newErrors.author_email = "Invalid email format";
    }

    if (!formValues.content?.trim()) {
      newErrors.content = "Review content is required";
    }

    if (formValues.rating < 1 || formValues.rating > 5) {
      newErrors.rating = "Rating must be between 1 and 5";
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validateForm()) {
      return;
    }

    try {
      const submitData: ReviewFormData = {
        product_id: productId || selectedProductId,
        author_name: formValues.author_name,
        author_email: formValues.author_email,
        author_url: formValues.author_url || "",
        content: formValues.content,
        rating: formValues.rating,
        status: formValues.status || "approve",
        verified: formValues.verified || false,
        meta: {},
      };

      console.log(fieldsData.meta);

      //get meta fields from reviewFields
      reviewFields
        .filter((field) => field.meta_field)
        .forEach((field) => {
          if (formValues[field.id] !== undefined) {
            submitData.meta[field.id] = formValues[field.id];
          }
        });

      console.log(submitData);

      await createReviewMutation.mutateAsync(submitData);
    } catch (error) {
      console.error("Form submission error:", error);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onOpenChange}>
      <DialogContent size="lg" className="max-w-2xl">
        <DialogHeader>
          <DialogTitle className="flex items-center space-x-2">
            <PlusIcon className="w-5 h-5" />
            <span>Add New Review</span>
          </DialogTitle>
        </DialogHeader>

        {isLoadingFields ? (
          <div className="p-8 text-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-2 text-gray-600">Loading form...</p>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Product Selection (if not pre-selected) */}
            {!productId && (
              <div className="space-y-2">
                <label className="block text-sm font-medium text-gray-700">
                  Product *
                </label>
                <Select.Root
                  value={selectedProductId?.toString() || ""}
                  onValueChange={(value) =>
                    setSelectedProductId(parseInt(value))
                  }
                >
                  <Select.Trigger className="w-full flex items-center justify-between px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <Select.Value placeholder="Select a product..." />
                    <Select.Icon>
                      <ChevronDownIcon />
                    </Select.Icon>
                  </Select.Trigger>
                  <Select.Portal>
                    <Select.Content className="bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto z-50">
                      <Select.Viewport>
                        {isLoadingProducts ? (
                          <div className="p-4 text-center text-gray-500">
                            Loading products...
                          </div>
                        ) : (
                          products.map((product) => (
                            <Select.Item
                              key={product.id}
                              value={product.id.toString()}
                              className="flex items-center space-x-3 px-3 py-2 hover:bg-gray-50 cursor-pointer data-[highlighted]:bg-blue-50 data-[highlighted]:outline-none"
                            >
                              <Select.ItemText className="flex items-center space-x-3">
                                {product.image && (
                                  <img
                                    src={product.image[0]}
                                    alt={product.name}
                                    className="w-8 h-8 object-cover rounded"
                                  />
                                )}
                                <div>
                                  <div className="font-medium">
                                    {product.name}
                                  </div>
                                  <div className="text-sm text-gray-500">
                                    ${product.price}
                                  </div>
                                </div>
                              </Select.ItemText>
                              <Select.ItemIndicator>
                                <CheckIcon />
                              </Select.ItemIndicator>
                            </Select.Item>
                          ))
                        )}
                      </Select.Viewport>
                    </Select.Content>
                  </Select.Portal>
                </Select.Root>
                {errors.product_id && (
                  <p className="text-sm text-red-600">{errors.product_id}</p>
                )}
              </div>
            )}{" "}
            {/* Dynamic Form Fields */}
            <div className="space-y-6">
              {/* Name and Email in grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {reviewFields
                  .filter((field: DynamicField) =>
                    ["author_name", "author_email"].includes(field.id)
                  )
                  .map((field: DynamicField) => (
                    <div key={field.id}>
                      <DynamicFieldRenderer
                        field={field}
                        value={formValues[field.id] || ""}
                        onChange={handleFieldChange}
                        validationRules={{
                          required: field.required,
                        }}
                      />
                      {errors[field.id] && (
                        <p className="text-sm text-red-600 mt-1">
                          {errors[field.id]}
                        </p>
                      )}
                    </div>
                  ))}
              </div>

              {/* Other fields */}
              {reviewFields
                .filter(
                  (field: DynamicField) =>
                    !["author_name", "author_email"].includes(field.id)
                )
                .map((field: DynamicField) => (
                  <div key={field.id}>
                    <DynamicFieldRenderer
                      field={field}
                      value={
                        formValues[field.id] ||
                        (field.type === "range"
                          ? 5
                          : field.type === "select"
                            ? "approve"
                            : "")
                      }
                      onChange={handleFieldChange}
                      validationRules={{
                        required: field.required,
                      }}
                    />
                    {errors[field.id] && (
                      <p className="text-sm text-red-600 mt-1">
                        {errors[field.id]}
                      </p>
                    )}
                  </div>
                ))}

              {/* Verified checkbox */}
              <div className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  id="verified"
                  checked={formValues.verified || false}
                  onChange={(e) =>
                    handleFieldChange("verified", false, e.target.checked)
                  }
                  className="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                />
                <label htmlFor="verified" className="text-sm text-gray-700">
                  Verified purchase
                </label>
              </div>
            </div>
            <div className="flex justify-end space-x-3 pt-4 border-t">
              <Button
                type="button"
                variant="outline"
                onClick={() => onOpenChange(false)}
                disabled={createReviewMutation.isPending}
              >
                Cancel
              </Button>
              <Button
                type="submit"
                disabled={createReviewMutation.isPending}
                className="flex items-center space-x-2"
              >
                {createReviewMutation.isPending ? (
                  <>
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                    <span>Creating...</span>
                  </>
                ) : (
                  <>
                    <PlusIcon className="w-4 h-4" />
                    <span>Create Review</span>
                  </>
                )}
              </Button>
            </div>
          </form>
        )}
      </DialogContent>
    </Dialog>
  );
};
