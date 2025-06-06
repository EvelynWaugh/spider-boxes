import React, {useState, useMemo} from "react";
import {useQuery, useMutation, useQueryClient} from "@tanstack/react-query";
import {
  createColumnHelper,
  getCoreRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  useReactTable,
  type SortingState,
  type ColumnFiltersState,
} from "@tanstack/react-table";
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  type DragEndEvent,
} from "@dnd-kit/core";
import {
  arrayMove,
  sortableKeyboardCoordinates,
  horizontalListSortingStrategy,
} from "@dnd-kit/sortable";
import {
  useSortable,
  SortableContext as SortableProvider,
} from "@dnd-kit/sortable";
import {CSS} from "@dnd-kit/utilities";
import {motion, AnimatePresence} from "framer-motion";
import {Button} from "@/components/ui/Button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/Dialog";
import {
  Pencil1Icon,
  TrashIcon,
  CheckIcon,
  Cross1Icon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from "@radix-ui/react-icons";
import {useAPI} from "../hooks/useAPI";
import {DynamicField, DynamicFieldRenderer} from "./DynamicFieldRenderer";

interface Review {
  id: number;
  product_id: number;
  product_name: string;
  author_name: string;
  author_email: string;
  rating: number;
  content: string;
  status: "approved" | "pending" | "spam" | "trash";
  date: string;
  date_gmt: string;
  author_url?: string;
  parent?: number;
  meta?: Record<string, any>;
}

interface ReviewsAppProps {
  productId?: number;
}

const columnHelper = createColumnHelper<Review>();

const SortableHeaderCell: React.FC<{
  id: string;
  children: React.ReactNode;
  className?: string;
}> = ({id, children, className}) => {
  const {attributes, listeners, setNodeRef, transform, transition, isDragging} =
    useSortable({id});

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    cursor: isDragging ? "grabbing" : "grab",
    opacity: isDragging ? 0.5 : 1,
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      {...attributes}
      {...listeners}
      className={`spider-boxes-table-header-cell sortable-header ${className || ""} ${
        isDragging ? "dragging" : ""
      }`}
      title="Drag to reorder columns"
    >
      <span className="flex items-center space-x-2">
        <span>{children}</span>
        <svg
          className="w-3 h-3 text-gray-400"
          fill="currentColor"
          viewBox="0 0 6 10"
        >
          <circle cx="1" cy="2" r="1" />
          <circle cx="1" cy="5" r="1" />
          <circle cx="1" cy="8" r="1" />
          <circle cx="5" cy="2" r="1" />
          <circle cx="5" cy="5" r="1" />
          <circle cx="5" cy="8" r="1" />
        </svg>
      </span>
    </div>
  );
};

export const ReviewsApp: React.FC<ReviewsAppProps> = ({productId}) => {
  const queryClient = useQueryClient();
  const {get, patch, del, getReviewFields} = useAPI();

  const [sorting, setSorting] = useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
  const [globalFilter, setGlobalFilter] = useState("");
  const [columnOrder, setColumnOrder] = useState<string[]>([
    "id",
    "product_name",
    "author_name",
    "rating",
    "status",
    "date",
    "actions",
  ]);
  const [selectedReview, setSelectedReview] = useState<Review | null>(null);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [fieldValues, setFieldValues] = useState<Record<string, any>>({});

  // Sensors for drag and drop
  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  // Fetch review fields
  const {data: fieldsData} = useQuery({
    queryKey: ["review-fields"],
    queryFn: getReviewFields,
    staleTime: 10 * 60 * 1000, // 10 minutes
  });

  const reviewFields: DynamicField[] = fieldsData?.fields
    ? Object.values(fieldsData.fields)
    : [];
  // Fetch reviews
  const {
    data: reviewsData,
    isLoading,
    error,
  } = useQuery({
    queryKey: ["reviews", productId],
    queryFn: () =>
      get(`/reviews${productId ? `?product_id=${productId}` : ""}`),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });

  console.log("Reviews data:", reviewsData);
  const reviews = reviewsData?.reviews || [];

  // Update review mutation
  const updateReviewMutation = useMutation({
    mutationFn: ({id, data}: {id: number; data: Partial<Review>}) =>
      patch(`/reviews/${id}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ["reviews"]});
    },
  });

  // Delete review mutation
  const deleteReviewMutation = useMutation({
    mutationFn: (id: number) => del(`/reviews/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ["reviews"]});
    },
  });

  const columns = useMemo(
    () => [
      columnHelper.accessor("id", {
        header: "ID",
        cell: (info) => `#${info.getValue()}`,
        size: 80,
      }),
      columnHelper.accessor("product_name", {
        header: "Product",
        cell: (info) => info.getValue(),
        filterFn: "includesString",
      }),
      columnHelper.accessor("author_name", {
        header: "Reviewer",
        cell: (info) => (
          <div>
            <div className="font-medium">{info.getValue()}</div>
            <div className="text-sm text-gray-500">
              {info.row.original.author_email}
            </div>
          </div>
        ),
        filterFn: "includesString",
      }),
      columnHelper.accessor("rating", {
        header: "Rating",
        cell: (info) => (
          <div className="flex items-center space-x-1">
            {[1, 2, 3, 4, 5].map((star) => (
              <svg
                key={star}
                className={`w-4 h-4 ${
                  star <= info.getValue() ? "text-yellow-400" : "text-gray-300"
                }`}
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            ))}
            <span className="ml-2 text-sm text-gray-600">
              ({info.getValue()})
            </span>
          </div>
        ),
        size: 150,
      }),
      columnHelper.accessor("status", {
        header: "Status",
        cell: (info) => {
          const status = info.getValue();
          const statusColors = {
            approved: "bg-green-100 text-green-800",
            pending: "bg-yellow-100 text-yellow-800",
            spam: "bg-red-100 text-red-800",
            trash: "bg-gray-100 text-gray-800",
          };

          return (
            <span
              className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColors[status]}`}
            >
              {status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
          );
        },
        filterFn: "equals",
      }),
      columnHelper.accessor("date", {
        header: "Date",
        cell: (info) => new Date(info.getValue()).toLocaleDateString(),
        size: 120,
      }),
      columnHelper.display({
        id: "actions",
        header: "Actions",
        cell: (info) => (
          <div className="flex space-x-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => {
                setSelectedReview(info.row.original);
                setIsDialogOpen(true);
              }}
            >
              Edit
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => {
                if (confirm("Are you sure you want to delete this review?")) {
                  deleteReviewMutation.mutate(info.row.original.id);
                }
              }}
            >
              Delete
            </Button>
          </div>
        ),
        size: 120,
      }),
    ],
    [deleteReviewMutation]
  );
  const table = useReactTable({
    data: reviews,
    columns,
    state: {
      sorting,
      columnFilters,
      globalFilter,
      columnOrder,
    },
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    onGlobalFilterChange: setGlobalFilter,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    initialState: {
      pagination: {
        pageSize: 20,
      },
    },
  });

  const handleDragEnd = (event: DragEndEvent) => {
    const {active, over} = event;

    if (active.id !== over?.id) {
      setColumnOrder((items) => {
        const oldIndex = items.indexOf(active.id as string);
        const newIndex = items.indexOf(over?.id as string);
        return arrayMove(items, oldIndex, newIndex);
      });
    }
  };

  const handleStatusChange = (
    reviewId: number,
    newStatus: Review["status"]
  ) => {
    updateReviewMutation.mutate({
      id: reviewId,
      data: {status: newStatus},
    });
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-red-600 p-4">
        Error loading reviews:{" "}
        {error instanceof Error ? error.message : "Unknown error"}
      </div>
    );
  }

  return (
    <div className="reviews-app">
      <div className="reviews-header">
        <h2 className="text-2xl font-bold mb-4">Product Reviews</h2>

        {/* Search and Filters */}
        <div className="mb-6 flex flex-col sm:flex-row gap-4">
          <div className="flex-1">
            <input
              type="text"
              placeholder="Search reviews..."
              value={globalFilter}
              onChange={(e) => setGlobalFilter(e.target.value)}
              className="search-input"
            />
          </div>
          <div className="flex gap-2">
            <select
              value={
                (columnFilters.find((f) => f.id === "status")
                  ?.value as string) || ""
              }
              onChange={(e) => {
                const value = e.target.value;
                setColumnFilters((prev) =>
                  value
                    ? [
                        ...prev.filter((f) => f.id !== "status"),
                        {id: "status", value},
                      ]
                    : prev.filter((f) => f.id !== "status")
                );
              }}
              className="filter-select"
            >
              <option value="">All Statuses</option>
              <option value="approved">Approved</option>
              <option value="pending">Pending</option>
              <option value="spam">Spam</option>
              <option value="trash">Trash</option>
            </select>
          </div>
        </div>
      </div>{" "}
      {/* Reviews Table */}
      {reviews.length === 0 ? (
        <div className="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
          <p className="text-gray-500 mb-4">No reviews found.</p>
        </div>
      ) : (
        <DndContext
          sensors={sensors}
          collisionDetection={closestCenter}
          onDragEnd={handleDragEnd}
        >
          <div className="spider-boxes-table">
            <SortableProvider
              items={columnOrder}
              strategy={horizontalListSortingStrategy}
            >
              <div className="spider-boxes-table-header">
                {columnOrder.map((columnId) => (
                  <SortableHeaderCell key={columnId} id={columnId}>
                    {columnId === "id" && "ID"}
                    {columnId === "product_name" && "Product"}
                    {columnId === "author_name" && "Reviewer"}
                    {columnId === "rating" && "Rating"}
                    {columnId === "status" && "Status"}
                    {columnId === "date" && "Date"}
                    {columnId === "actions" && "Actions"}
                  </SortableHeaderCell>
                ))}
              </div>
            </SortableProvider>
            <div className="divide-y divide-gray-200">
              <AnimatePresence>
                {table.getRowModel().rows.map((row) => (
                  <motion.div
                    key={row.id}
                    layout
                    initial={{opacity: 0}}
                    animate={{opacity: 1}}
                    exit={{opacity: 0}}
                    className="spider-boxes-table-row"
                  >
                    {columnOrder.map((columnId) => (
                      <div key={columnId} className="spider-boxes-table-cell">
                        {columnId === "id" && (
                          <span className="font-mono text-xs bg-gray-50 px-2 py-1 rounded">
                            #{row.original.id}
                          </span>
                        )}
                        {columnId === "product_name" && (
                          <span className="font-medium">
                            {row.original.product_name}
                          </span>
                        )}
                        {columnId === "author_name" && (
                          <div>
                            <div className="font-medium">
                              {row.original.author_name}
                            </div>
                            <div className="text-sm text-gray-500">
                              {row.original.author_email}
                            </div>
                          </div>
                        )}
                        {columnId === "rating" && (
                          <div className="flex items-center space-x-1">
                            {[1, 2, 3, 4, 5].map((star) => (
                              <svg
                                key={star}
                                className={`w-4 h-4 ${
                                  star <= row.original.rating
                                    ? "text-yellow-400"
                                    : "text-gray-300"
                                }`}
                                fill="currentColor"
                                viewBox="0 0 20 20"
                              >
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                              </svg>
                            ))}
                            <span className="ml-2 text-sm text-gray-600">
                              ({row.original.rating})
                            </span>
                          </div>
                        )}
                        {columnId === "status" && (
                          <span
                            className={`spider-boxes-badge ${
                              row.original.status === "approved"
                                ? "spider-boxes-badge-success"
                                : row.original.status === "pending"
                                  ? "spider-boxes-badge-warning"
                                  : row.original.status === "spam"
                                    ? "spider-boxes-badge-danger"
                                    : "spider-boxes-badge-secondary"
                            }`}
                          >
                            {row.original.status.charAt(0).toUpperCase() +
                              row.original.status.slice(1)}
                          </span>
                        )}
                        {columnId === "date" && (
                          <span className="text-gray-500">
                            {new Date(row.original.date).toLocaleDateString()}
                          </span>
                        )}
                        {columnId === "actions" && (
                          <div className="flex space-x-2">
                            <button
                              onClick={() => {
                                setSelectedReview(row.original);
                                setIsDialogOpen(true);
                              }}
                              className="text-primary-600 hover:text-primary-900"
                              title="Edit review"
                            >
                              <Pencil1Icon className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => {
                                if (
                                  confirm(
                                    "Are you sure you want to delete this review?"
                                  )
                                ) {
                                  deleteReviewMutation.mutate(row.original.id);
                                }
                              }}
                              className="text-red-600 hover:text-red-900"
                              title="Delete review"
                            >
                              <TrashIcon className="w-4 h-4" />
                            </button>
                            {row.original.status === "pending" && (
                              <button
                                onClick={() =>
                                  handleStatusChange(
                                    row.original.id,
                                    "approved"
                                  )
                                }
                                className="text-green-600 hover:text-green-900"
                                title="Approve review"
                              >
                                <CheckIcon className="w-4 h-4" />
                              </button>
                            )}
                            {row.original.status === "approved" && (
                              <button
                                onClick={() =>
                                  handleStatusChange(row.original.id, "pending")
                                }
                                className="text-yellow-600 hover:text-yellow-900"
                                title="Mark as pending"
                              >
                                <Cross1Icon className="w-4 h-4" />
                              </button>
                            )}
                          </div>
                        )}
                      </div>
                    ))}
                  </motion.div>
                ))}
              </AnimatePresence>
            </div>
          </div>
        </DndContext>
      )}
      {/* Pagination */}
      <div className="flex items-center justify-between py-4">
        <div className="text-sm text-gray-700">
          Showing{" "}
          {table.getState().pagination.pageIndex *
            table.getState().pagination.pageSize +
            1}{" "}
          to{" "}
          {Math.min(
            (table.getState().pagination.pageIndex + 1) *
              table.getState().pagination.pageSize,
            table.getFilteredRowModel().rows.length
          )}{" "}
          of {table.getFilteredRowModel().rows.length} results
        </div>
        <div className="flex items-center space-x-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => table.previousPage()}
            disabled={!table.getCanPreviousPage()}
          >
            <ChevronLeftIcon className="w-4 h-4" />
            Previous
          </Button>
          <span className="text-sm text-gray-700">
            Page {table.getState().pagination.pageIndex + 1} of{" "}
            {table.getPageCount()}
          </span>
          <Button
            variant="outline"
            size="sm"
            onClick={() => table.nextPage()}
            disabled={!table.getCanNextPage()}
          >
            Next
            <ChevronRightIcon className="w-4 h-4" />
          </Button>
        </div>
      </div>
      {/* Edit Review Dialog */}
      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent size="lg">
          <DialogHeader>
            <DialogTitle>Edit Review</DialogTitle>
          </DialogHeader>
          <div>
            {selectedReview && reviewFields.length > 0 ? (
              <div className="space-y-4">
                {reviewFields.map((field) => {
                  // Map field IDs to review properties

                  let currentValue;
                  switch (field.id) {
                    case "review_author_name":
                      currentValue =
                        fieldValues[field.id] || selectedReview.author_name;
                      break;
                    case "review_author_email":
                      currentValue =
                        fieldValues[field.id] || selectedReview.author_email;
                      break;
                    case "review_date":
                      currentValue =
                        fieldValues[field.id] || selectedReview.date;
                      break;
                    case "review_content":
                      currentValue =
                        fieldValues[field.id] || selectedReview.content;
                      break;

                    case "review_status":
                      currentValue =
                        fieldValues[field.id] || selectedReview.status;
                      break;

                    case "rating":
                      currentValue =
                        fieldValues[field.id] || selectedReview.rating;
                      break;
                    default:
                      if (field.meta_field) {
                        // For custom fields, use meta
                        currentValue =
                          fieldValues[field.id] ||
                          selectedReview.meta?.[field.id] ||
                          field.value;
                      } else {
                        currentValue = fieldValues[field.id] || field.value;
                      }
                  }

                  return (
                    <DynamicFieldRenderer
                      key={field.id}
                      field={field}
                      value={currentValue}
                      onChange={(fieldId, isMeta, value) => {
                        console.log(fieldId, isMeta, value);
                        setFieldValues((prev) => ({
                          ...prev,
                          [fieldId]: value,
                        }));

                        // Update selectedReview for immediate UI feedback
                        if (selectedReview) {
                          const updatedReview = {...selectedReview};
                          switch (fieldId) {
                            case "review_author_name":
                              updatedReview.author_name = value;
                              break;
                            case "review_author_email":
                              updatedReview.author_email = value;
                              break;
                            case "review_date":
                              updatedReview.date = value;
                              break;
                            case "review_content":
                              updatedReview.content = value;
                              break;
                            case "review_status":
                              updatedReview.status = value;
                              break;
                            case "rating":
                              updatedReview.rating = value;
                              break;
                          }

                          if (isMeta) {
                            if (!updatedReview.meta) {
                              updatedReview.meta = {};
                            }
                            updatedReview.meta[fieldId] = value;
                          }
                          setSelectedReview(updatedReview);
                        }
                      }}
                    />
                  );
                })}
                <div className="flex justify-end space-x-3 pt-4">
                  <Button
                    variant="outline"
                    onClick={() => {
                      setIsDialogOpen(false);
                      setFieldValues({});
                    }}
                  >
                    Cancel
                  </Button>
                  <Button
                    onClick={() => {
                      // Prepare data for update
                      const updateData: any = {};

                      // Map field values back to API format
                      Object.entries(fieldValues).forEach(
                        ([fieldId, value]) => {
                          switch (fieldId) {
                            case "review_author_name":
                              updateData.author_name = value;
                              break;
                            case "review_author_email":
                              updateData.author_email = value;
                              break;
                            case "review_date":
                              updateData.date = value;
                              break;
                            case "review_content":
                              updateData.content = value;
                              break;

                            case "review_status":
                              updateData.status = value;
                              break;
                            case "rating":
                              updateData.rating = value;
                              break;
                            default:
                              // Custom fields go into meta
                              if (!updateData.meta) updateData.meta = {};

                              updateData.meta[fieldId] = value;
                          }
                        }
                      );

                      updateReviewMutation.mutate({
                        id: selectedReview.id,
                        data: updateData,
                      });
                      setIsDialogOpen(false);
                      setFieldValues({});
                    }}
                    disabled={updateReviewMutation.isPending}
                  >
                    {updateReviewMutation.isPending
                      ? "Saving..."
                      : "Save Changes"}
                  </Button>
                </div>
              </div>
            ) : (
              <div className="text-center py-8">
                <p className="text-gray-500">
                  No review selected or fields not loaded.
                </p>
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
};
