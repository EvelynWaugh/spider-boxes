import React, {useState, useMemo} from "react";
import {useQuery, useMutation, useQueryClient} from "@tanstack/react-query";
import {
  createColumnHelper,
  flexRender,
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
import {Dialog} from "@/components/ui/Dialog";
import {useAPI} from "../hooks/useAPI";

interface Review {
  id: number;
  product_id: number;
  product_name: string;
  reviewer_name: string;
  reviewer_email: string;
  rating: number;
  comment: string;
  status: "approved" | "pending" | "spam" | "trash";
  date_created: string;
  custom_fields?: Record<string, any>;
}

interface ReviewsAppProps {
  productId?: number;
}

const columnHelper = createColumnHelper<Review>();

const SortableHeader: React.FC<{
  id: string;
  children: React.ReactNode;
}> = ({id, children}) => {
  const {attributes, listeners, setNodeRef, transform, transition} =
    useSortable({id});

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  return (
    <th
      ref={setNodeRef}
      style={style}
      {...attributes}
      {...listeners}
      className="sortable-header"
    >
      {children}
    </th>
  );
};

export const ReviewsApp: React.FC<ReviewsAppProps> = ({productId}) => {
  const queryClient = useQueryClient();
  const {get, patch, del} = useAPI();

  const [sorting, setSorting] = useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
  const [globalFilter, setGlobalFilter] = useState("");
  const [columnOrder, setColumnOrder] = useState<string[]>([
    "id",
    "product_name",
    "reviewer_name",
    "rating",
    "status",
    "date_created",
    "actions",
  ]);
  const [selectedReview, setSelectedReview] = useState<Review | null>(null);
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  // Sensors for drag and drop
  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  // Fetch reviews
  const {
    data: reviews = [],
    isLoading,
    error,
  } = useQuery({
    queryKey: ["reviews", productId],
    queryFn: () =>
      get(`/reviews${productId ? `?product_id=${productId}` : ""}`),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });

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
      columnHelper.accessor("reviewer_name", {
        header: "Reviewer",
        cell: (info) => (
          <div>
            <div className="font-medium">{info.getValue()}</div>
            <div className="text-sm text-gray-500">
              {info.row.original.reviewer_email}
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
      columnHelper.accessor("date_created", {
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
        pageSize: 10,
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
      </div>

      {/* Reviews Table */}
      <div className="reviews-table-container">
        <DndContext
          sensors={sensors}
          collisionDetection={closestCenter}
          onDragEnd={handleDragEnd}
        >
          <div className="table-wrapper">
            <table className="reviews-table">
              <thead>
                <SortableProvider
                  items={columnOrder}
                  strategy={horizontalListSortingStrategy}
                >
                  <tr>
                    {table.getHeaderGroups().map((headerGroup) =>
                      headerGroup.headers.map((header) => (
                        <SortableHeader key={header.id} id={header.id}>
                          <div
                            className={`table-header ${
                              header.column.getCanSort() ? "sortable" : ""
                            }`}
                            onClick={header.column.getToggleSortingHandler()}
                          >
                            {flexRender(
                              header.column.columnDef.header,
                              header.getContext()
                            )}
                            {header.column.getIsSorted() && (
                              <span className="sort-indicator">
                                {header.column.getIsSorted() === "desc"
                                  ? " ↓"
                                  : " ↑"}
                              </span>
                            )}
                          </div>
                        </SortableHeader>
                      ))
                    )}
                  </tr>
                </SortableProvider>
              </thead>
              <tbody>
                <AnimatePresence>
                  {table.getRowModel().rows.map((row) => (
                    <motion.tr
                      key={row.id}
                      initial={{opacity: 0, y: 20}}
                      animate={{opacity: 1, y: 0}}
                      exit={{opacity: 0, y: -20}}
                      transition={{duration: 0.2}}
                      className="table-row"
                    >
                      {row.getVisibleCells().map((cell) => (
                        <td key={cell.id} className="table-cell">
                          {flexRender(
                            cell.column.columnDef.cell,
                            cell.getContext()
                          )}
                        </td>
                      ))}
                    </motion.tr>
                  ))}
                </AnimatePresence>
              </tbody>
            </table>
          </div>
        </DndContext>

        {/* Pagination */}
        <div className="table-pagination">
          <div className="pagination-info">
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
          <div className="pagination-controls">
            <Button
              variant="outline"
              size="sm"
              onClick={() => table.previousPage()}
              disabled={!table.getCanPreviousPage()}
            >
              Previous
            </Button>
            <span className="pagination-pages">
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
            </Button>
          </div>
        </div>
      </div>

      {/* Edit Review Dialog */}
      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <div className="dialog-content">
          <h3 className="dialog-title">Edit Review</h3>
          {selectedReview && (
            <div className="space-y-4">
              <div>
                <label className="form-label">Status</label>
                <select
                  value={selectedReview.status}
                  onChange={(e) => {
                    const newStatus = e.target.value as Review["status"];
                    handleStatusChange(selectedReview.id, newStatus);
                    setSelectedReview({...selectedReview, status: newStatus});
                  }}
                  className="form-select"
                >
                  <option value="approved">Approved</option>
                  <option value="pending">Pending</option>
                  <option value="spam">Spam</option>
                  <option value="trash">Trash</option>
                </select>
              </div>
              <div>
                <label className="form-label">Comment</label>
                <textarea
                  value={selectedReview.comment}
                  onChange={(e) =>
                    setSelectedReview({
                      ...selectedReview,
                      comment: e.target.value,
                    })
                  }
                  className="form-textarea"
                  rows={4}
                />
              </div>
              <div className="dialog-actions">
                <Button
                  variant="outline"
                  onClick={() => setIsDialogOpen(false)}
                >
                  Cancel
                </Button>
                <Button
                  onClick={() => {
                    updateReviewMutation.mutate({
                      id: selectedReview.id,
                      data: {
                        comment: selectedReview.comment,
                        status: selectedReview.status,
                      },
                    });
                    setIsDialogOpen(false);
                  }}
                >
                  Save Changes
                </Button>
              </div>
            </div>
          )}
        </div>
      </Dialog>
    </div>
  );
};
