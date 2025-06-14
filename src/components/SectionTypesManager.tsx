import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { motion } from "framer-motion";
import { PlusIcon, PencilIcon, TrashIcon, SearchIcon } from "@/components/icons";
import { Button } from "./ui/Button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/Dialog";
import { SectionTypeForm, type SectionTypeData } from "./forms/SectionTypeForm";
import { useAPI } from "@/hooks/useAPI";
import { doAction, applyFilters } from "@/hooks/createHooks";
import { convertSectionTypeToSectionTypeData, type SectionType } from "@/utils/section";

export function SectionTypesManager() {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingSectionType, setEditingSectionType] = useState<SectionType | null>(null);
  const [searchTerm, setSearchTerm] = useState("");
  const queryClient = useQueryClient();
  const api = useAPI();

  const { data: sectionTypesResponse = {}, isLoading } = useQuery({
    queryKey: ["section-types"],
    queryFn: () => api.get("/section-types"),
  });

  // Convert nested response to flat array
  const sectionTypes: SectionType[] = Object.entries(sectionTypesResponse.section_types || {}).map(([id, type]: [string, any]) => ({
    id,
    name: type.name || type.class_name?.split("\\").pop() || id,
    type: type.type || id,
    class_name: type.class_name || "",
    description: type.description || "",
    icon: type.icon || "section",
    supports: type.supports || [],
    category: type.category || "general",
    is_active: type.is_active !== false,
  }));

  const createSectionTypeMutation = useMutation({
    mutationFn: (sectionTypeData: Partial<SectionType>) => api.post("/section-types", sectionTypeData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["section-types"] });
      setIsFormOpen(false);
      setEditingSectionType(null);
      doAction("spiderBoxes.sectionTypeCreated");
    },
    onError: (error) => {
      console.error("Error creating section type:", error);
    },
  });

  const updateSectionTypeMutation = useMutation({
    mutationFn: ({ id, ...sectionTypeData }: Partial<SectionType> & { id: string }) => api.put(`/section-types/${id}`, sectionTypeData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["section-types"] });
      setIsFormOpen(false);
      setEditingSectionType(null);
      doAction("spiderBoxes.sectionTypeUpdated");
    },
    onError: (error) => {
      console.error("Error updating section type:", error);
    },
  });

  const deleteSectionTypeMutation = useMutation({
    mutationFn: (id: string) => api.del(`/section-types/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["section-types"] });
      doAction("spiderBoxes.sectionTypeDeleted");
    },
  });

  const handleCreateSectionType = () => {
    setEditingSectionType(null);
    setIsFormOpen(true);
    createSectionTypeMutation.reset();
    updateSectionTypeMutation.reset();
  };

  const handleEditSectionType = (sectionType: SectionType) => {
    setEditingSectionType(sectionType);
    setIsFormOpen(true);
    createSectionTypeMutation.reset();
    updateSectionTypeMutation.reset();
  };

  const handleCloseForm = () => {
    setIsFormOpen(false);
    setEditingSectionType(null);
    createSectionTypeMutation.reset();
    updateSectionTypeMutation.reset();
  };

  const handleDeleteSectionType = (id: string) => {
    if (confirm("Are you sure you want to delete this section type?")) {
      deleteSectionTypeMutation.mutate(id);
    }
  };

  const handleFormSubmit = (sectionTypeData: SectionTypeData) => {
    console.log("Form submitted with data:", sectionTypeData);

    const requiredFields = ["type", "name"];
    const missingFields = requiredFields.filter((field) => !sectionTypeData[field as keyof SectionTypeData]);

    if (missingFields.length > 0) {
      console.error(`Missing required fields: ${missingFields.join(", ")}`);
      return;
    }

    const processedSectionType = applyFilters("spiderBoxes.sectionTypeToSave", sectionTypeData, editingSectionType) as SectionTypeData;

    if (editingSectionType && editingSectionType.id !== "new") {
      console.log("Updating existing section type:", editingSectionType.id);
      updateSectionTypeMutation.mutate({ ...processedSectionType, id: editingSectionType.id });
    } else {
      console.log("Creating new section type");
      createSectionTypeMutation.mutate(processedSectionType);
    }
  };

  const getErrorMessage = (error: any): string => {
    if (!error) return "";
    if (typeof error === "string") return error;
    if (error.message) return error.message;
    if (error.response?.data?.message) return error.response.data.message;
    if (error.response?.statusText) return error.response.statusText;
    return "An unexpected error occurred";
  };

  // Filter section types based on search term
  const filteredSectionTypes = sectionTypes.filter(
    (sectionType) =>
      sectionType.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      sectionType.type.toLowerCase().includes(searchTerm.toLowerCase()) ||
      sectionType.description.toLowerCase().includes(searchTerm.toLowerCase()),
  );

  // Apply filters for extensibility
  const displaySectionTypes = applyFilters("spiderBoxes.sectionTypesTableData", filteredSectionTypes) as SectionType[];

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="spider-boxes-section-types-manager">
      {/* Header */}

      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-medium text-gray-900">Sections Types</h2>
          <p className="text-sm text-gray-600">Manage section types that define the structure and behavior of sections.</p>
        </div>
        <Button onClick={handleCreateSectionType} className="spider-boxes-button">
          <PlusIcon className="w-4 h-4 mr-2" />
          Add Section
        </Button>
      </div>

      {displaySectionTypes.length === 0 ? (
        <div className="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
          <p className="text-gray-500 mb-4">No section types created yet.</p>
          <Button onClick={handleCreateSectionType} className="spider-boxes-create-button">
            <PlusIcon className="w-4 h-4 mr-2" />
            Create your first section type
          </Button>
        </div>
      ) : (
        <div className="spider-boxes-table-container">
          {/* Search */}
          <div className="spider-boxes-search-container">
            <div className="spider-boxes-search-wrapper">
              <SearchIcon className="spider-boxes-search-icon" />
              <input
                type="text"
                placeholder="Search section types..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="spider-boxes-search-input"
              />
            </div>
          </div>
          <div className="spider-boxes-table">
            <div className="spider-boxes-table-header">
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Section Type</div>
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Class Name</div>
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Description</div>
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Supports</div>
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Category</div>
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Status</div>
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Actions</div>
            </div>

            <div className="spider-boxes-table-body">
              {displaySectionTypes.map((sectionType) => (
                <motion.div key={sectionType.id} initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="spider-boxes-table-row">
                  <div className="spider-boxes-table-cell">
                    <div className="flex items-center space-x-3">
                      <div className="spider-boxes-section-type-icon">{sectionType.icon}</div>
                      <div>
                        <div className="font-medium text-gray-900">{sectionType.name}</div>
                        <div className="text-sm text-gray-500">{sectionType.type}</div>
                      </div>
                    </div>
                  </div>

                  <div className="spider-boxes-table-cell">
                    <div className="flex items-center space-x-3">
                      <div>
                        <div className="font-medium text-gray-900">{sectionType.class_name || "—"}</div>
                      </div>
                    </div>
                  </div>

                  <div className="spider-boxes-table-cell">
                    <div className="text-sm text-gray-900 max-w-xs truncate">{sectionType.description || "—"}</div>
                  </div>

                  <div className="spider-boxes-table-cell">
                    <div className="flex flex-wrap gap-1">
                      {sectionType.supports.slice(0, 3).map((feature) => (
                        <span
                          key={feature}
                          className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                        >
                          {feature}
                        </span>
                      ))}
                      {sectionType.supports.length > 3 && (
                        <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                          +{sectionType.supports.length - 3}
                        </span>
                      )}
                    </div>
                  </div>

                  <div className="spider-boxes-table-cell">
                    <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 capitalize">
                      {sectionType.category}
                    </span>
                  </div>

                  <div className="spider-boxes-table-cell">
                    <span
                      className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                        sectionType.is_active ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800"
                      }`}
                    >
                      {sectionType.is_active ? "Active" : "Inactive"}
                    </span>
                  </div>

                  <div className="spider-boxes-table-cell">
                    <div className="flex items-center space-x-2">
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleEditSectionType(sectionType)}
                        className="spider-boxes-edit-button"
                      >
                        <PencilIcon className="spider-boxes-icon" />
                        Edit
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleDeleteSectionType(sectionType.id)}
                        className="spider-boxes-delete-button"
                      >
                        <TrashIcon className="spider-boxes-icon" />
                        Delete
                      </Button>
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Section Type Form Dialog */}
      <Dialog open={isFormOpen} onOpenChange={handleCloseForm}>
        <DialogContent size="lg" className="spider-boxes-dialog">
          <DialogHeader>
            <DialogTitle>{editingSectionType ? "Edit Section Type" : "Create Section Type"}</DialogTitle>
          </DialogHeader>

          <SectionTypeForm
            sectionType={editingSectionType ? convertSectionTypeToSectionTypeData(editingSectionType) : undefined}
            onSave={handleFormSubmit}
            onCancel={handleCloseForm}
            error={getErrorMessage(createSectionTypeMutation.error || updateSectionTypeMutation.error)}
            isLoading={createSectionTypeMutation.isPending || updateSectionTypeMutation.isPending}
          />
        </DialogContent>
      </Dialog>
    </div>
  );
}
