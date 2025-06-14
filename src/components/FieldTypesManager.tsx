import React, { useState, useMemo } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { motion } from "framer-motion";
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon } from "@/components/icons";
import { Button } from "./ui/Button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/Dialog";
import { FieldTypeForm, type FieldTypeData } from "@/components/forms/FieldTypeForm";

import { useAPI } from "@/hooks/useAPI";
import { doAction, applyFilters } from "@/hooks/createHooks";

// FieldType - used in FieldTypesManager (backend/API structure)
interface FieldType {
  id: string;
  name?: string; // e.g., 'Text', 'Number', 'Select', etc.
  type: string; // e.g., 'text', 'number', 'select', etc.
  class_name?: string; // PHP class name for the field type
  label?: string;
  description: string;
  icon?: string;
  supports: string[];
  is_active: boolean;
  created_at?: string; // ISO date string
}

interface FieldPreview {
  config: {
    id: string;
    type: string;
    label: string;
    description?: string;
    placeholder?: string;
    required?: boolean;
    options?: Record<string, string>; // For select/radio fields
    [key: string]: any;
  };
  value?: any;
}

// Convert FieldType to FieldTypeData format
const convertFieldTypeToFieldTypeData = (fieldType: FieldType | null): FieldTypeData | undefined => {
  if (!fieldType) return undefined;
  return {
    id: fieldType.id,
    type: fieldType.type,
    description: fieldType.description,
    icon: fieldType.icon,
    supports: fieldType.supports,
    is_active: fieldType.is_active,
  };
};

// Convert FieldTypeData to FieldType format
const convertFieldTypeDataToFieldType = (fieldTypeData: FieldTypeData): Partial<FieldType> => {
  const fieldType: Partial<FieldType> = {
    id: fieldTypeData.id, // Always include the ID ('new' or existing)
    type: fieldTypeData.type,
    description: fieldTypeData.description || "",
    icon: fieldTypeData.icon,
    supports: fieldTypeData.supports || [],
    is_active: fieldTypeData.is_active ?? true,
  };

  return fieldType;
};

export const FieldTypesManager: React.FC = () => {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingFieldType, setEditingFieldType] = useState<FieldType | null>(null);
  const [isPreviewOpen, setIsPreviewOpen] = useState(false);
  const [selectedFieldType, setSelectedFieldType] = useState<FieldType | null>(null);
  const [previewConfig, setPreviewConfig] = useState<FieldPreview>({
    config: {
      id: "preview_field",
      type: "text",
      label: "Preview Field",
    },
  });
  const [searchTerm, setSearchTerm] = useState("");

  const queryClient = useQueryClient();
  const api = useAPI();

  // Fetch field types
  const { data: fieldTypes = [], isLoading } = useQuery({
    queryKey: ["field-types"],
    queryFn: () => api.get("/field-types"),
  });

  // Filter field types based on search term
  const filteredFieldTypes = useMemo(() => {
    if (!searchTerm) return fieldTypes;
    const searchLower = searchTerm.toLowerCase();
    return fieldTypes.filter(
      (fieldType: FieldType) =>
        fieldType.type.toLowerCase().includes(searchLower) || fieldType.description.toLowerCase().includes(searchLower),
    );
  }, [fieldTypes, searchTerm]);

  // Mutations
  const createFieldTypeMutation = useMutation({
    mutationFn: (fieldTypeData: Partial<FieldType>) => api.post("/field-types", fieldTypeData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["field-types"] });
      setIsFormOpen(false);
      setEditingFieldType(null);
      doAction("spiderBoxes.fieldTypeCreated");
    },
    onError: (error) => {
      console.error("Error creating field type:", error);
    },
  });

  const updateFieldTypeMutation = useMutation({
    mutationFn: ({ id, ...fieldTypeData }: Partial<FieldType> & { id: string }) => api.put(`/field-types/${id}`, fieldTypeData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["field-types"] });
      setIsFormOpen(false);
      setEditingFieldType(null);
      doAction("spiderBoxes.fieldTypeUpdated");
    },
    onError: (error) => {
      console.error("Error updating field type:", error);
    },
  });

  const deleteFieldTypeMutation = useMutation({
    mutationFn: (id: string) => api.del(`/field-types/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["field-types"] });
      doAction("spiderBoxes.fieldTypeDeleted");
    },
  });

  // Event handlers
  const handleCreateFieldType = () => {
    setEditingFieldType(null);
    setIsFormOpen(true);
    // Reset any previous errors
    createFieldTypeMutation.reset();
    updateFieldTypeMutation.reset();
  };

  const handleEditFieldType = (fieldType: FieldType) => {
    setEditingFieldType(fieldType);
    setIsFormOpen(true);
    // Reset any previous errors
    createFieldTypeMutation.reset();
    updateFieldTypeMutation.reset();
  };

  const handleCloseForm = () => {
    setIsFormOpen(false);
    setEditingFieldType(null);
    // Reset any errors when closing
    createFieldTypeMutation.reset();
    updateFieldTypeMutation.reset();
  };

  const handleDeleteFieldType = (id: string) => {
    if (confirm("Are you sure you want to delete this field type?")) {
      deleteFieldTypeMutation.mutate(id);
    }
  };

  const handleFormSubmit = (fieldTypeData: FieldTypeData) => {
    console.log("Form submitted with data:", fieldTypeData);

    const fieldTypeToSave = convertFieldTypeDataToFieldType(fieldTypeData);
    console.log("Converted field type data:", fieldTypeToSave);

    // Validate required fields
    const requiredFields = ["type"];
    const missingFields = requiredFields.filter((field) => !fieldTypeToSave[field as keyof FieldType]);

    if (missingFields.length > 0) {
      console.error(`Missing required fields: ${missingFields.join(", ")}`);
      return;
    }

    // Apply filters for extensibility
    const processedFieldType = applyFilters("spiderBoxes.fieldTypeToSave", fieldTypeToSave, editingFieldType) as Partial<FieldType>;

    if (editingFieldType && editingFieldType.id !== "new") {
      console.log("Updating existing field type:", editingFieldType.id);
      updateFieldTypeMutation.mutate({ ...processedFieldType, id: editingFieldType.id });
    } else {
      console.log("Creating new field type");
      createFieldTypeMutation.mutate(processedFieldType);
    }
  };

  const handlePreviewField = (fieldType: FieldType) => {
    setSelectedFieldType(fieldType);
    setPreviewConfig({
      config: {
        id: "preview_field",
        type: fieldType.type,
        label: `Preview ${fieldType.type}`,
        description: fieldType.description,
        placeholder: `Enter ${fieldType.type.toLowerCase()}...`,
        required: false,
        ...fieldType.supports,
      },
      value: getDefaultValue(fieldType.type),
    });
    setIsPreviewOpen(true);
  };

  const getDefaultValue = (type: string) => {
    switch (type) {
      case "checkbox":
        return false;
      case "radio":
      case "select":
        return "";
      case "range":
        return 50;
      case "switcher":
        return false;
      case "number":
        return 0;
      case "date":
      case "datetime":
        return "";
      case "textarea":
      case "wysiwyg":
        return "";
      case "media":
        return null;
      default:
        return "";
    }
  };

  // Helper function to get error message
  const getErrorMessage = (error: any): string => {
    if (!error) return "";
    if (typeof error === "string") return error;
    if (error.message) return error.message;
    if (error.response?.data?.message) return error.response.data.message;
    if (error.response?.statusText) return error.response.statusText;
    return "An unexpected error occurred";
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-32">
        <div className="spider-boxes-loading"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-medium text-gray-900">Field Types</h2>
          <p className="text-sm text-gray-600">Manage available field types that can be used in forms and components.</p>
        </div>
        <Button onClick={handleCreateFieldType} className="spider-boxes-button">
          <PlusIcon className="w-4 h-4 mr-2" />
          Add Field Type
        </Button>
      </div>

      {/* Search and Filter */}
      <div className="field-types-filters">
        <div className="search-wrapper">
          <input
            type="text"
            placeholder="Search field types..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="search-input"
          />
        </div>
      </div>

      {fieldTypes.length === 0 ? (
        <div className="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
          <p className="text-gray-500 mb-4">No field types created yet.</p>
          <Button onClick={handleCreateFieldType} className="spider-boxes-button">
            <PlusIcon className="w-4 h-4 mr-2" />
            Create your first field type
          </Button>
        </div>
      ) : (
        <div className="spider-boxes-table">
          <div className="spider-boxes-table-header">
            <div className="spider-boxes-table-header-cell">Type</div>
            <div className="spider-boxes-table-header-cell">Class Name (PHP)</div>
            <div className="spider-boxes-table-header-cell">Description</div>
            <div className="spider-boxes-table-header-cell">Supports</div>
            <div className="spider-boxes-table-header-cell">Status</div>
            <div className="spider-boxes-table-header-cell">Created</div>
            <div className="spider-boxes-table-header-cell">Actions</div>
          </div>
          <div className="divide-y divide-gray-200">
            {filteredFieldTypes.map((fieldType: FieldType) => (
              <motion.div
                key={fieldType.id}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -20 }}
                transition={{ duration: 0.2 }}
                className="spider-boxes-table-row"
              >
                <div className="spider-boxes-table-cell">
                  <div className="flex items-center space-x-3">
                    <div>
                      <div className="font-medium text-gray-900">{fieldType.type}</div>
                    </div>
                  </div>
                </div>

                <div className="spider-boxes-table-cell">
                  <div className="flex items-center space-x-3">
                    <div>
                      <div className="font-medium text-gray-900">{fieldType.class_name}</div>
                    </div>
                  </div>
                </div>

                <div className="spider-boxes-table-cell">
                  <div className="text-sm text-gray-900 max-w-xs truncate">{fieldType.description}</div>
                </div>
                <div className="spider-boxes-table-cell">
                  <div className="flex flex-wrap gap-1">
                    {fieldType.supports.slice(0, 3).map((feature) => (
                      <span
                        key={feature}
                        className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                      >
                        {feature}
                      </span>
                    ))}
                    {fieldType.supports.length > 3 && (
                      <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        +{fieldType.supports.length - 3} more
                      </span>
                    )}
                  </div>
                </div>
                <div className="spider-boxes-table-cell">
                  <span
                    className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                      fieldType.is_active ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800"
                    }`}
                  >
                    {fieldType.is_active ? "Active" : "Inactive"}
                  </span>
                </div>
                <div className="spider-boxes-table-cell">
                  <div className="flex items-center space-x-3">
                    <div>
                      <div className="font-medium text-gray-900">{fieldType.created_at}</div>
                    </div>
                  </div>
                </div>
                <div className="spider-boxes-table-cell">
                  <div className="flex items-center space-x-2">
                    {" "}
                    <button
                      onClick={() => handlePreviewField(fieldType)}
                      className="text-blue-600 hover:text-blue-900"
                      title="Preview field type"
                    >
                      <EyeIcon className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => handleEditFieldType(fieldType)}
                      className="text-primary-600 hover:text-primary-900"
                      title="Edit field type"
                    >
                      <PencilIcon className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => handleDeleteFieldType(fieldType.id)}
                      className="text-red-600 hover:text-red-900"
                      title="Delete field type"
                    >
                      <TrashIcon className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      )}

      {filteredFieldTypes.length === 0 && searchTerm && (
        <div className="empty-state">
          <div className="empty-state-icon">🔍</div>
          <h3 className="empty-state-title">No field types found</h3>
          <p className="empty-state-description">Try adjusting your search criteria.</p>
        </div>
      )}

      {/* Field Type Form Dialog */}
      <Dialog open={isFormOpen} onOpenChange={setIsFormOpen}>
        <DialogContent size="lg">
          <DialogHeader>
            <DialogTitle>{editingFieldType ? "Edit Field Type" : "Create New Field Type"}</DialogTitle>
          </DialogHeader>
          <FieldTypeForm
            fieldType={convertFieldTypeToFieldTypeData(editingFieldType)}
            onSave={handleFormSubmit}
            onCancel={handleCloseForm}
            error={getErrorMessage(createFieldTypeMutation.error) || getErrorMessage(updateFieldTypeMutation.error)}
            isLoading={createFieldTypeMutation.isPending || updateFieldTypeMutation.isPending}
          />
        </DialogContent>
      </Dialog>

      {/* Field Preview Dialog */}
      <Dialog open={isPreviewOpen} onOpenChange={setIsPreviewOpen}>
        <DialogContent size="lg">
          <DialogHeader>
            <DialogTitle>Field Preview: {selectedFieldType?.label || selectedFieldType?.name}</DialogTitle>
          </DialogHeader>

          <div className="field-preview">
            {selectedFieldType && (
              <div className="preview-info">
                <div className="preview-meta">
                  <div className="preview-type">
                    <span className="meta-label">Type:</span>
                    <code>{selectedFieldType.name}</code>
                  </div>
                </div>
                <div className="preview-description">{selectedFieldType.description}</div>
              </div>
            )}

            <div className="preview-field">
              <h4 className="preview-title">Live Preview</h4>
              <div className="preview-container"></div>
            </div>

            {previewConfig.value !== undefined && previewConfig.value !== "" && (
              <div className="preview-output">
                <h4 className="output-title">Current Value</h4>
                <pre className="output-value">{JSON.stringify(previewConfig.value, null, 2)}</pre>
              </div>
            )}

            <div className="preview-actions">
              <Button variant="outline" onClick={() => setIsPreviewOpen(false)}>
                Close
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
};
