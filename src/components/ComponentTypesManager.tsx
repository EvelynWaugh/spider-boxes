import React, { useState, useMemo } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { motion } from "framer-motion";
import { PlusIcon, PencilIcon, TrashIcon } from "@/components/icons";
import { Button } from "./ui/Button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/Dialog";
import { ComponentTypeForm, type ComponentTypeData } from "@/components/forms/ComponentTypeForm";
import { useAPI } from "@/hooks/useAPI";
import { doAction, applyFilters } from "@/hooks/createHooks";

// ComponentType - used in ComponentTypesManager (backend/API structure)
interface ComponentType {
  id: string;
  name?: string; // e.g., 'Accordion', 'Tab', 'Row', etc.
  type: string; // e.g., 'accordion', 'tab', 'row', etc.
  class_name?: string; // PHP class name for the component type
  description: string;
  icon?: string;
  supports: string[];
  children?: string[];
  parent?: string;
  category?: string;
  is_active: boolean;
  created_at?: string; // ISO date string
}

// Convert ComponentType to ComponentTypeData format
const convertComponentTypeToComponentTypeData = (componentType: ComponentType | null): ComponentTypeData | undefined => {
  if (!componentType) return undefined;
  return {
    id: componentType.id,
    type: componentType.type,
    name: componentType.name,
    class_name: componentType.class_name,
    description: componentType.description,
    icon: componentType.icon,
    supports: componentType.supports,
    children: componentType.children,
    parent: componentType.parent,
    category: componentType.category,
    is_active: componentType.is_active,
  };
};

// Convert ComponentTypeData to ComponentType format
const convertComponentTypeDataToComponentType = (componentTypeData: ComponentTypeData): Partial<ComponentType> => {
  const componentType: Partial<ComponentType> = {
    id: componentTypeData.id, // Always include the ID ('new' or existing)
    type: componentTypeData.type,
    name: componentTypeData.name,
    class_name: componentTypeData.class_name,
    description: componentTypeData.description || "",
    icon: componentTypeData.icon,
    supports: componentTypeData.supports || [],
    children: componentTypeData.children || [],
    parent: componentTypeData.parent,
    category: componentTypeData.category || "layout",
    is_active: componentTypeData.is_active ?? true,
  };

  return componentType;
};

export const ComponentTypesManager: React.FC = () => {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingComponentType, setEditingComponentType] = useState<ComponentType | null>(null);
  const [searchTerm, setSearchTerm] = useState("");

  const queryClient = useQueryClient();
  const api = useAPI();

  // Fetch component types
  const { data: componentTypesResponse = {}, isLoading } = useQuery({
    queryKey: ["component-types"],
    queryFn: () => api.get("/component-types"),
  });

  // Convert response to array format for table display
  const componentTypes = useMemo(() => {
    if (!componentTypesResponse.component_types) return [];

    return Object.entries(componentTypesResponse.component_types).map(([id, type]: [string, any]) => ({
      id,
      name: type.name || type.class_name?.split("\\").pop() || id,
      type: id,
      class_name: type.class_name || "",
      description: type.description || "",
      icon: type.icon || "🔧",
      supports: type.supports || [],
      children: type.children || [],
      parent: type.parent || "",
      category: type.category || "layout",
      is_active: type.is_active ?? true,
      created_at: type.created_at || "",
    }));
  }, [componentTypesResponse]);

  // Filter component types based on search term
  const filteredComponentTypes = useMemo(() => {
    if (!searchTerm) return componentTypes;
    const searchLower = searchTerm.toLowerCase();
    return componentTypes.filter(
      (componentType: ComponentType) =>
        componentType.type.toLowerCase().includes(searchLower) ||
        componentType.description.toLowerCase().includes(searchLower) ||
        (componentType.name && componentType.name.toLowerCase().includes(searchLower)),
    );
  }, [componentTypes, searchTerm]);

  // Mutations
  const createComponentTypeMutation = useMutation({
    mutationFn: (componentTypeData: Partial<ComponentType>) => api.post("/component-types", componentTypeData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["component-types"] });
      setIsFormOpen(false);
      setEditingComponentType(null);
      doAction("spiderBoxes.componentTypeCreated");
    },
    onError: (error) => {
      console.error("Error creating component type:", error);
    },
  });

  const updateComponentTypeMutation = useMutation({
    mutationFn: ({ id, ...componentTypeData }: Partial<ComponentType> & { id: string }) =>
      api.put(`/component-types/${id}`, componentTypeData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["component-types"] });
      setIsFormOpen(false);
      setEditingComponentType(null);
      doAction("spiderBoxes.componentTypeUpdated");
    },
    onError: (error) => {
      console.error("Error updating component type:", error);
    },
  });

  const deleteComponentTypeMutation = useMutation({
    mutationFn: (id: string) => api.del(`/component-types/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["component-types"] });
      doAction("spiderBoxes.componentTypeDeleted");
    },
  });

  // Event handlers
  const handleCreateComponentType = () => {
    setEditingComponentType(null);
    setIsFormOpen(true);
    // Reset any previous errors
    createComponentTypeMutation.reset();
    updateComponentTypeMutation.reset();
  };

  const handleEditComponentType = (componentType: ComponentType) => {
    setEditingComponentType(componentType);
    setIsFormOpen(true);
    // Reset any previous errors
    createComponentTypeMutation.reset();
    updateComponentTypeMutation.reset();
  };

  const handleCloseForm = () => {
    setIsFormOpen(false);
    setEditingComponentType(null);
    // Reset any errors when closing
    createComponentTypeMutation.reset();
    updateComponentTypeMutation.reset();
  };

  const handleDeleteComponentType = (id: string) => {
    if (confirm("Are you sure you want to delete this component type?")) {
      deleteComponentTypeMutation.mutate(id);
    }
  };

  const handleFormSubmit = (componentTypeData: ComponentTypeData) => {
    console.log("Form submitted with data:", componentTypeData);

    const componentTypeToSave = convertComponentTypeDataToComponentType(componentTypeData);
    console.log("Converted component type data:", componentTypeToSave);

    // Validate required fields
    const requiredFields = ["type", "name"];
    const missingFields = requiredFields.filter((field) => !componentTypeToSave[field as keyof ComponentType]);

    if (missingFields.length > 0) {
      console.error(`Missing required fields: ${missingFields.join(", ")}`);
      return;
    }

    // Apply filters for extensibility
    const processedComponentType = applyFilters(
      "spiderBoxes.componentTypeToSave",
      componentTypeToSave,
      editingComponentType,
    ) as Partial<ComponentType>;

    if (editingComponentType && editingComponentType.id !== "new") {
      console.log("Updating existing component type:", editingComponentType.id);
      updateComponentTypeMutation.mutate({ ...processedComponentType, id: editingComponentType.id });
    } else {
      console.log("Creating new component type");
      createComponentTypeMutation.mutate(processedComponentType);
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
          <h2 className="text-lg font-medium text-gray-900">Component Types</h2>
          <p className="text-sm text-gray-600">Manage available component types that can be used in sections and layouts.</p>
        </div>
        <Button onClick={handleCreateComponentType} className="spider-boxes-button">
          <PlusIcon className="w-4 h-4 mr-2" />
          Add Component Type
        </Button>
      </div>

      {/* Search and Filter */}
      <div className="component-types-filters">
        <div className="search-wrapper">
          <input
            type="text"
            placeholder="Search component types..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="search-input"
          />
        </div>
      </div>

      {componentTypes.length === 0 ? (
        <div className="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
          <p className="text-gray-500 mb-4">No component types created yet.</p>
          <Button onClick={handleCreateComponentType} className="spider-boxes-button">
            <PlusIcon className="w-4 h-4 mr-2" />
            Create your first component type
          </Button>
        </div>
      ) : (
        <div className="spider-boxes-table">
          <div className="spider-boxes-table-header">
            <div className="spider-boxes-table-header-cell">Type</div>
            <div className="spider-boxes-table-header-cell">Name</div>
            <div className="spider-boxes-table-header-cell">Class Name (PHP)</div>
            <div className="spider-boxes-table-header-cell">Description</div>
            <div className="spider-boxes-table-header-cell">Category</div>
            <div className="spider-boxes-table-header-cell">Supports</div>
            <div className="spider-boxes-table-header-cell">Children</div>
            <div className="spider-boxes-table-header-cell">Status</div>
            <div className="spider-boxes-table-header-cell">Actions</div>
          </div>
          <div className="divide-y divide-gray-200">
            {filteredComponentTypes.map((componentType: ComponentType) => (
              <motion.div
                key={componentType.id}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -20 }}
                transition={{ duration: 0.2 }}
                className="spider-boxes-table-row"
              >
                <div className="spider-boxes-table-cell">
                  <div className="flex items-center space-x-3">
                    <div>
                      <div className="font-medium text-gray-900">{componentType.type}</div>
                    </div>
                  </div>
                </div>

                <div className="spider-boxes-table-cell">
                  <div className="flex items-center space-x-3">
                    <div>
                      <div className="font-medium text-gray-900">{componentType.name || componentType.type}</div>
                    </div>
                  </div>
                </div>

                <div className="spider-boxes-table-cell">
                  <div className="flex items-center space-x-3">
                    <div>
                      <div className="font-mono text-xs text-gray-600">{componentType.class_name}</div>
                    </div>
                  </div>
                </div>

                <div className="spider-boxes-table-cell">
                  <div className="text-sm text-gray-900 max-w-xs truncate">{componentType.description}</div>
                </div>

                <div className="spider-boxes-table-cell">
                  <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    {componentType.category}
                  </span>
                </div>

                <div className="spider-boxes-table-cell">
                  <div className="flex flex-wrap gap-1">
                    {componentType.supports.slice(0, 3).map((feature) => (
                      <span
                        key={feature}
                        className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                      >
                        {feature}
                      </span>
                    ))}
                    {componentType.supports.length > 3 && (
                      <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        +{componentType.supports.length - 3} more
                      </span>
                    )}
                  </div>
                </div>

                <div className="spider-boxes-table-cell">
                  <div className="flex flex-wrap gap-1">
                    {componentType.children && componentType.children.length > 0 ? (
                      componentType.children.slice(0, 2).map((child) => (
                        <span
                          key={child}
                          className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                        >
                          {child}
                        </span>
                      ))
                    ) : (
                      <span className="text-xs text-gray-500">None</span>
                    )}
                    {componentType.children && componentType.children.length > 2 && (
                      <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        +{componentType.children.length - 2} more
                      </span>
                    )}
                  </div>
                </div>

                <div className="spider-boxes-table-cell">
                  <span
                    className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                      componentType.is_active ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800"
                    }`}
                  >
                    {componentType.is_active ? "Active" : "Inactive"}
                  </span>
                </div>

                <div className="spider-boxes-table-cell">
                  <div className="flex items-center space-x-2">
                    <button
                      onClick={() => handleEditComponentType(componentType)}
                      className="text-primary-600 hover:text-primary-900"
                      title="Edit component type"
                    >
                      <PencilIcon className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => handleDeleteComponentType(componentType.id)}
                      className="text-red-600 hover:text-red-900"
                      title="Delete component type"
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

      {filteredComponentTypes.length === 0 && searchTerm && (
        <div className="empty-state">
          <div className="empty-state-icon">🔍</div>
          <h3 className="empty-state-title">No component types found</h3>
          <p className="empty-state-description">Try adjusting your search criteria.</p>
        </div>
      )}

      {/* Component Type Form Dialog */}
      <Dialog open={isFormOpen} onOpenChange={setIsFormOpen}>
        <DialogContent size="lg">
          <DialogHeader>
            <DialogTitle>{editingComponentType ? "Edit Component Type" : "Create New Component Type"}</DialogTitle>
          </DialogHeader>
          <ComponentTypeForm
            componentType={convertComponentTypeToComponentTypeData(editingComponentType)}
            onSave={handleFormSubmit}
            onCancel={handleCloseForm}
            error={getErrorMessage(createComponentTypeMutation.error) || getErrorMessage(updateComponentTypeMutation.error)}
            isLoading={createComponentTypeMutation.isPending || updateComponentTypeMutation.isPending}
          />
        </DialogContent>
      </Dialog>
    </div>
  );
};
