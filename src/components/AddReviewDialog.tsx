import React, {useEffect} from "react";
import {useMutation, useQuery, useQueryClient} from "@tanstack/react-query";
import {useForm} from "@tanstack/react-form";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/Dialog";
import {Button} from "@/components/ui/Button";
import * as Select from "@radix-ui/react-select";
import {
  StarIcon,
  StarFilledIcon,
  PlusIcon,
  ChevronDownIcon,
  CheckIcon,
} from "@radix-ui/react-icons";
import {useAPI} from "../hooks/useAPI";

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
}

export const AddReviewDialog: React.FC<AddReviewDialogProps> = ({
  isOpen,
  onOpenChange,
  productId,
}) => {
  const queryClient = useQueryClient();
  const {get, post} = useAPI();

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
    },
    onError: (error: any) => {
      console.error("Failed to create review:", error);
    },
  });
  // TanStack Form
  const form = useForm({
    defaultValues: {
      product_id: productId || 0,
      author_name: "",
      author_email: "",
      author_url: "",
      content: "",
      rating: 5,
      status: "approve" as ReviewFormData["status"],
      verified: false,
    },
    onSubmit: async ({value}) => {
      try {
        await createReviewMutation.mutateAsync(value);
      } catch (error) {
        console.error("Form submission error:", error);
      }
    },
  });

  // Reset form when dialog opens
  useEffect(() => {
    if (isOpen) {
      form.reset();
    }
  }, [isOpen, form]);

  const renderStarRating = (
    value: number,
    onChange: (rating: number) => void
  ) => {
    return (
      <div className="flex items-center space-x-1">
        {[1, 2, 3, 4, 5].map((star) => (
          <button
            key={star}
            type="button"
            onClick={() => onChange(star)}
            className="text-yellow-400 hover:text-yellow-500 transition-colors"
          >
            {star <= value ? (
              <StarFilledIcon className="w-6 h-6" />
            ) : (
              <StarIcon className="w-6 h-6" />
            )}
          </button>
        ))}
        <span className="ml-2 text-sm text-gray-600">
          {value} star{value !== 1 ? "s" : ""}
        </span>
      </div>
    );
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

        <form
          onSubmit={(e) => {
            e.preventDefault();
            e.stopPropagation();
            form.handleSubmit();
          }}
          className="space-y-6"
        >
          {!productId && (
            <form.Field
              name="product_id"
              validators={{
                onChange: ({value}) =>
                  !value || value === 0 ? "Product is required" : undefined,
              }}
            >
              {(field) => (
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-gray-700">
                    Product *
                  </label>
                  <Select.Root
                    value={field.state.value?.toString()}
                    onValueChange={(value) =>
                      field.handleChange(parseInt(value))
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
                  {field.state.meta.errors && (
                    <p className="text-sm text-red-600">
                      {field.state.meta.errors[0]}
                    </p>
                  )}
                </div>
              )}
            </form.Field>
          )}

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <form.Field
              name="author_name"
              validators={{
                onChange: ({value}) =>
                  !value?.trim() ? "Reviewer name is required" : undefined,
              }}
            >
              {(field) => (
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-gray-700">
                    Reviewer Name *
                  </label>
                  <input
                    type="text"
                    value={field.state.value}
                    onChange={(e) => field.handleChange(e.target.value)}
                    onBlur={field.handleBlur}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter reviewer name"
                  />
                  {field.state.meta.errors && (
                    <p className="text-sm text-red-600">
                      {field.state.meta.errors[0]}
                    </p>
                  )}
                </div>
              )}
            </form.Field>

            <form.Field
              name="author_email"
              validators={{
                onChange: ({value}) => {
                  if (!value?.trim()) return "Email is required";
                  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    return "Invalid email format";
                  }
                  return undefined;
                },
              }}
            >
              {(field) => (
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-gray-700">
                    Email *
                  </label>
                  <input
                    type="email"
                    value={field.state.value}
                    onChange={(e) => field.handleChange(e.target.value)}
                    onBlur={field.handleBlur}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter email address"
                  />
                  {field.state.meta.errors && (
                    <p className="text-sm text-red-600">
                      {field.state.meta.errors[0]}
                    </p>
                  )}
                </div>
              )}
            </form.Field>
          </div>

          <form.Field name="author_url">
            {(field) => (
              <div className="space-y-2">
                <label className="block text-sm font-medium text-gray-700">
                  Website URL (Optional)
                </label>
                <input
                  type="url"
                  value={field.state.value}
                  onChange={(e) => field.handleChange(e.target.value)}
                  onBlur={field.handleBlur}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="https://example.com"
                />
              </div>
            )}
          </form.Field>

          <form.Field
            name="rating"
            validators={{
              onChange: ({value}) =>
                value < 1 || value > 5
                  ? "Rating must be between 1 and 5"
                  : undefined,
            }}
          >
            {(field) => (
              <div className="space-y-2">
                <label className="block text-sm font-medium text-gray-700">
                  Rating *
                </label>
                {renderStarRating(field.state.value, field.handleChange)}
                {field.state.meta.errors && (
                  <p className="text-sm text-red-600">
                    {field.state.meta.errors[0]}
                  </p>
                )}
              </div>
            )}
          </form.Field>

          <form.Field
            name="content"
            validators={{
              onChange: ({value}) =>
                !value?.trim() ? "Review content is required" : undefined,
            }}
          >
            {(field) => (
              <div className="space-y-2">
                <label className="block text-sm font-medium text-gray-700">
                  Review Content *
                </label>
                <textarea
                  value={field.state.value}
                  onChange={(e) => field.handleChange(e.target.value)}
                  onBlur={field.handleBlur}
                  rows={4}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Write the review content..."
                />
                {field.state.meta.errors && (
                  <p className="text-sm text-red-600">
                    {field.state.meta.errors[0]}
                  </p>
                )}
              </div>
            )}
          </form.Field>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {" "}
            <form.Field name="status">
              {(field) => (
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-gray-700">
                    Status
                  </label>
                  <Select.Root
                    value={field.state.value}
                    onValueChange={(value) =>
                      field.handleChange(value as ReviewFormData["status"])
                    }
                  >
                    <Select.Trigger className="w-full flex items-center justify-between px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                      <Select.Value />
                      <Select.Icon>
                        <ChevronDownIcon />
                      </Select.Icon>
                    </Select.Trigger>
                    <Select.Portal>
                      <Select.Content className="bg-white border border-gray-200 rounded-md shadow-lg z-50">
                        <Select.Viewport>
                          <Select.Item
                            value="approve"
                            className="px-3 py-2 hover:bg-gray-50 cursor-pointer data-[highlighted]:bg-blue-50 data-[highlighted]:outline-none"
                          >
                            <Select.ItemText>Approved</Select.ItemText>
                            <Select.ItemIndicator>
                              <CheckIcon />
                            </Select.ItemIndicator>
                          </Select.Item>
                          <Select.Item
                            value="hold"
                            className="px-3 py-2 hover:bg-gray-50 cursor-pointer data-[highlighted]:bg-blue-50 data-[highlighted]:outline-none"
                          >
                            <Select.ItemText>Hold</Select.ItemText>
                            <Select.ItemIndicator>
                              <CheckIcon />
                            </Select.ItemIndicator>
                          </Select.Item>
                          <Select.Item
                            value="spam"
                            className="px-3 py-2 hover:bg-gray-50 cursor-pointer data-[highlighted]:bg-blue-50 data-[highlighted]:outline-none"
                          >
                            <Select.ItemText>Spam</Select.ItemText>
                            <Select.ItemIndicator>
                              <CheckIcon />
                            </Select.ItemIndicator>
                          </Select.Item>
                          <Select.Item
                            value="trash"
                            className="px-3 py-2 hover:bg-gray-50 cursor-pointer data-[highlighted]:bg-blue-50 data-[highlighted]:outline-none"
                          >
                            <Select.ItemText>Trash</Select.ItemText>
                            <Select.ItemIndicator>
                              <CheckIcon />
                            </Select.ItemIndicator>
                          </Select.Item>
                        </Select.Viewport>
                      </Select.Content>
                    </Select.Portal>
                  </Select.Root>
                </div>
              )}
            </form.Field>
            <form.Field name="verified">
              {(field) => (
                <div className="flex items-center space-x-2 pt-6">
                  <input
                    type="checkbox"
                    id="verified"
                    checked={field.state.value}
                    onChange={(e) => field.handleChange(e.target.checked)}
                    className="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                  />
                  <label htmlFor="verified" className="text-sm text-gray-700">
                    Verified purchase
                  </label>
                </div>
              )}
            </form.Field>
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
            <form.Subscribe
              selector={(state) => [state.canSubmit, state.isSubmitting]}
            >
              {([canSubmit, isSubmitting]) => (
                <Button
                  type="submit"
                  disabled={
                    !canSubmit || isSubmitting || createReviewMutation.isPending
                  }
                  className="flex items-center space-x-2"
                >
                  {isSubmitting || createReviewMutation.isPending ? (
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
            </form.Subscribe>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};
