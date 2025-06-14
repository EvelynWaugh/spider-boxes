import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { motion } from "framer-motion";
import { PlusIcon, PencilIcon, TrashIcon } from "@/components/icons";
import { Button } from "./ui/Button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/Dialog";
import { ComponentForm, convertComponentToComponentData } from "./forms/ComponentForm";
import { useAPI } from "@/hooks/useAPI";
import { doAction, applyFilters } from "@/hooks/createHooks";
import { type Component } from "@/utils/component";

export function ComponentsManager() {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingComponent, setEditingComponent] = useState<Component | null>(null);
  const queryClient = useQueryClient();
  const api = useAPI();

  const { data: components = [], isLoading } = useQuery({
    queryKey: ["components"],
    queryFn: () => api.get("/components"),
  });

  const createComponentMutation = useMutation({
    mutationFn: (componentData: Partial<Component>) => api.post("/components", componentData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["components"] });
      setIsFormOpen(false);
      setEditingComponent(null);
      doAction("spiderBoxes.componentCreated");
    },
    onError: (error) => {
      console.error("Error creating component:", error);
    },
  });

  const updateComponentMutation = useMutation({
    mutationFn: ({ id, ...componentData }: Partial<Component> & { id: string }) => api.put(`/components/${id}`, componentData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["components"] });
      setIsFormOpen(false);
      setEditingComponent(null);
      doAction("spiderBoxes.componentUpdated");
    },
    onError: (error) => {
      console.error("Error updating component:", error);
    },
  });

  const deleteComponentMutation = useMutation({
    mutationFn: (id: string) => api.del(`/components/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["components"] });
      doAction("spiderBoxes.componentDeleted");
    },
  });

  const handleCreateComponent = () => {
    setEditingComponent(null);
    setIsFormOpen(true);
    createComponentMutation.reset();
    updateComponentMutation.reset();
  };

  const handleEditComponent = (component: Component) => {
    setEditingComponent(component);
    setIsFormOpen(true);
    createComponentMutation.reset();
    updateComponentMutation.reset();
  };

  const handleCloseForm = () => {
    setIsFormOpen(false);
    setEditingComponent(null);
    createComponentMutation.reset();
    updateComponentMutation.reset();
  };

  const handleDeleteComponent = (id: string) => {
    if (confirm("Are you sure you want to delete this component?")) {
      deleteComponentMutation.mutate(id);
    }
  };

  const handleFormSubmit = (componentData: Component) => {
    console.log("Form submitted with data:", componentData);

    // Validate required fields
    const requiredFields = ["type", "title"];
    const missingFields = requiredFields.filter((field) => !componentData[field as keyof Component]);

    if (missingFields.length > 0) {
      console.error(`Missing required fields: ${missingFields.join(", ")}`);
      return;
    }

    // Apply filters for extensibility
    const processedComponent = applyFilters("spiderBoxes.componentToSave", componentData, editingComponent) as Component;

    if (editingComponent && editingComponent.id !== "new") {
      console.log("Updating existing component:", editingComponent.id);
      updateComponentMutation.mutate({ ...processedComponent, id: editingComponent.id });
    } else {
      console.log("Creating new component");
      createComponentMutation.mutate(processedComponent);
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

  // Helper function to get component icon
  const getComponentIcon = (type: string) => {
    const icons: Record<string, string> = {
      accordion: "📋",
      tab: "📑",
      tabs: "📑",
      row: "▦",
      column: "▨",
      pane: "📄",
    };
    return icons[type] || "🔧";
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
          <h2 className="text-lg font-medium text-gray-900">Components</h2>
          <p className="text-sm text-gray-600">Manage reusable components that can be used to build sections.</p>
        </div>
        <Button onClick={handleCreateComponent} className="spider-boxes-button">
          <PlusIcon className="w-4 h-4 mr-2" />
          Add Component
        </Button>
      </div>

      {components.length === 0 ? (
        <div className="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
          <p className="text-gray-500 mb-4">No components created yet.</p>
          <Button onClick={handleCreateComponent} className="spider-boxes-button">
            <PlusIcon className="w-4 h-4 mr-2" />
            Create your first component
          </Button>
        </div>
      ) : (
        <div className="spider-boxes-table">
          <div className="spider-boxes-table-header">
            <div className="spider-boxes-table-header-cell">Component ID</div>
            <div className="spider-boxes-table-header-cell">Title</div>
            <div className="spider-boxes-table-header-cell">Type</div>
            <div className="spider-boxes-table-header-cell">Context</div>
            <div className="spider-boxes-table-header-cell">Children</div>
            <div className="spider-boxes-table-header-cell">Status</div>
            <div className="spider-boxes-table-header-cell">Actions</div>
          </div>
          <div className="divide-y divide-gray-200">
            {components.map((component: Component) => (
              <motion.div
                key={component.id}
                layout
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                className="spider-boxes-table-row"
              >
                <div className="spider-boxes-table-cell font-mono text-xs bg-gray-50">
                  <div className="flex items-center space-x-2">
                    <span>{getComponentIcon(component.type)}</span>
                    <span>{component.id}</span>
                  </div>
                </div>
                <div className="spider-boxes-table-cell font-medium">{component.title}</div>
                <div className="spider-boxes-table-cell">
                  <span className="spider-boxes-badge spider-boxes-badge-primary">{component.type}</span>
                </div>
                <div className="spider-boxes-table-cell text-gray-500">{component.context || "default"}</div>
                <div className="spider-boxes-table-cell text-gray-500">{Object.keys(component.children || {}).length} children</div>
                <div className="spider-boxes-table-cell">
                  <span className={`spider-boxes-badge ${component.is_active ? "spider-boxes-badge-success" : "spider-boxes-badge-gray"}`}>
                    {component.is_active ? "Active" : "Inactive"}
                  </span>
                </div>
                <div className="spider-boxes-table-cell">
                  <div className="flex space-x-2">
                    <button
                      onClick={() => handleEditComponent(component)}
                      className="text-primary-600 hover:text-primary-900"
                      title="Edit component"
                    >
                      <PencilIcon className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => handleDeleteComponent(component.id)}
                      className="text-red-600 hover:text-red-900"
                      title="Delete component"
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

      <Dialog open={isFormOpen} onOpenChange={setIsFormOpen}>
        <DialogContent size="lg">
          <DialogHeader>
            <DialogTitle>{editingComponent ? "Edit Component" : "Create New Component"}</DialogTitle>
          </DialogHeader>
          <ComponentForm
            component={convertComponentToComponentData(editingComponent)}
            onSave={handleFormSubmit}
            onCancel={handleCloseForm}
            error={getErrorMessage(createComponentMutation.error) || getErrorMessage(updateComponentMutation.error)}
            isLoading={createComponentMutation.isPending || updateComponentMutation.isPending}
          />
        </DialogContent>
      </Dialog>
    </div>
  );
}
