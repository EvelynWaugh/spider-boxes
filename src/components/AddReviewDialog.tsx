import React, { useEffect, useState, useCallback } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/Dialog";
import { Button } from "@/components/ui/Button";

import { PlusIcon, ChevronDownIcon, CheckIcon } from "@radix-ui/react-icons";
import { useForm } from "@tanstack/react-form";
import { useAPI } from "@/hooks/useAPI";

import { type DynamicField, DynamicFieldRenderer } from "./DynamicFieldRenderer";

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

export const AddReviewDialog: React.FC<AddReviewDialogProps> = ({ isOpen, onOpenChange, productId }) => {
  const queryClient = useQueryClient();
  const { get, post, getReviewFields } = useAPI();

  // State for form values

  const [selectedProductId, setSelectedProductId] = useState<number>(productId || 0);

  // Fetch review fields configuration
  const { data: fieldsData, isLoading: isLoadingFields } = useQuery({
    queryKey: ["review-fields"],
    queryFn: getReviewFields,
    enabled: isOpen,
  });

  const reviewFields: DynamicField[] = fieldsData?.fields ? Object.values(fieldsData.fields) : []; // Fetch reviews with server-side pagination

  const form = useForm({
    defaultValues: {} as Record<string, any>,
    onSubmit: async ({ value }) => {
      // The submit handler will now be called by form.handleSubmit()
      // The `value` here is the entire form state.
      console.log("Form submitted with values:", value);
      handleSubmit(value);
    },
    // You can keep onSubmit validation if you want a final check
    // validators: {
    //   onSubmit: ({value}) => {
    //     console.log("erroe", value);
    //   },
    // },
  });

  const productField: DynamicField = {
    id: "product_id",
    type: "product_select",
    title: "Product",
    placeholder: "Select a product",
    description: "Select the product for this review",
    value: selectedProductId,
    required: true,
  };

  const verifiedtField: DynamicField = {
    id: "verified",
    type: "checkbox",
    title: "Verified Purchase",
    value: false,
  };

  // Create review mutation
  const createReviewMutation = useMutation({
    mutationFn: (data: ReviewFormData) => post("/reviews", data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["reviews"] });
      onOpenChange(false);
    },
    onError: (error: any) => {
      console.error("Failed to create review:", error);
    },
  });

  // Reset form when dialog opens
  useEffect(() => {
    if (isOpen) {
      setSelectedProductId(productId || 0);
    }
  }, [isOpen, productId]);

  const handleSubmit = useCallback(
    (formValues: Record<string, any>) => {
      try {
        const updateData: any = {};

        // Map field values back to API format
        Object.entries(formValues).forEach(([fieldId, value]) => {
          switch (fieldId) {
            case "product_id":
              updateData.product_id = value;
              break;
            case "author_name":
              updateData.author_name = value;
              break;
            case "author_email":
              updateData.author_email = value;
              break;
            case "author_url":
              updateData.author_url = value || "";
              break;
            case "date":
              updateData.date = value;
              break;
            case "content":
              updateData.content = value;
              break;
            case "status":
              updateData.status = value;
              break;
            case "rating":
              updateData.rating = value;
              break;
            case "verified":
              updateData.verified = value;
              break;
            default:
              if (!updateData.meta) updateData.meta = {};
              updateData.meta[fieldId] = value;
          }
        });

        console.log("BEFORE SUBMIT", updateData);

        createReviewMutation.mutate(updateData);
      } catch (error) {
        console.error("Form submission error:", error);
      }
    },
    [createReviewMutation, productId, selectedProductId],
  );

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
          <form
            onSubmit={(e) => {
              e.preventDefault();
              e.stopPropagation();
              form.handleSubmit();
            }}
            className="space-y-6"
          >
            {/* Product Selection (if not pre-selected) */}
            {!productId && (
              <DynamicFieldRenderer
                field={productField}
                formApi={form}
                validationRules={{
                  required: productField.required,
                  ...productField.validation,
                }}
              />
            )}
            {/* Dynamic Form Fields */}
            <div className="space-y-6">
              {/* Name and Email in grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {reviewFields
                  .filter((field: DynamicField) => ["author_name", "author_email"].includes(field.id))
                  .map((field: DynamicField) => (
                    <div key={field.id}>
                      <DynamicFieldRenderer
                        key={field.id}
                        field={field}
                        formApi={form}
                        validationRules={{
                          required: field.required,
                          ...field.validation,
                        }}
                      />
                    </div>
                  ))}
              </div>

              {/* Other fields */}
              {reviewFields
                .filter((field: DynamicField) => !["author_name", "author_email"].includes(field.id))
                .map((field: DynamicField) => (
                  <div key={field.id}>
                    <DynamicFieldRenderer
                      key={field.id}
                      field={field}
                      formApi={form}
                      validationRules={{
                        required: field.required,
                        ...field.validation,
                      }}
                    />
                  </div>
                ))}

              {/* Verified checkbox */}

              <DynamicFieldRenderer
                field={verifiedtField}
                formApi={form}
                validationRules={{
                  required: verifiedtField.required,
                  ...verifiedtField.validation,
                }}
              />
            </div>

            <div className="flex justify-end space-x-3 pt-4 border-t">
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={createReviewMutation.isPending}>
                Cancel
              </Button>

              <form.Subscribe
                selector={(state) => [state.canSubmit, state.isSubmitting]}
                children={([canSubmit, isSubmitting]) => (
                  <Button
                    type="submit"
                    // onClick={handleSaveChanges}
                    variant="primary"
                    disabled={!canSubmit || isSubmitting || createReviewMutation.isPending}
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
                )}
              />
            </div>
          </form>
        )}
      </DialogContent>
    </Dialog>
  );
};
