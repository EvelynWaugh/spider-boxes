import React, { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { motion, AnimatePresence } from "framer-motion";
import { Button } from "./ui/Button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "./ui/Dialog";
import { useAPI } from "../hooks/useAPI";
import { FieldRenderer } from "./FieldRenderer";

interface Component {
  id: string;
  type: string;
  title: string;
  description?: string;
  parent_id?: string;
  section_id?: string;
  context?: string;
  settings?: Record<string, any>;
  children?: Record<string, any>;
  sort_order?: number;
  is_active?: boolean;
  capability?: string;
  created_at?: string;
  updated_at?: string;
}

interface ComponentType {
  id: string;
  name: string;
  class_name: string;
  category: string;
  icon: string;
  description: string;
  supports: string[];
  children?: string[];
  parent?: string;
}

export const ComponentsManager: React.FC = () => {
  const queryClient = useQueryClient();
  const { get, post, patch, del } = useAPI();

  const [selectedComponent, setSelectedComponent] = useState<Component | null>(null);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [isCreateMode, setIsCreateMode] = useState(false);
  const [componentForm, setComponentForm] = useState<Partial<Component>>({});

  // Fetch components
  const { data: components = [], isLoading: componentsLoading } = useQuery({
    queryKey: ["components"],
    queryFn: () => get("/components"),
  });
  // Fetch component types
  const { data: componentTypesResponse = {}, isLoading: typesLoading } = useQuery({
    queryKey: ["component-types"],
    queryFn: () => get("/component-types"),
  });

  // Extract component types from the response and convert to array format
  const componentTypes = Object.entries(componentTypesResponse.component_types || {}).map(([id, type]: [string, any]) => ({
    id,
    name: type.class?.split("\\").pop() || id,
    class_name: type.class || "",
    category: type.category || "general",
    icon: type.icon || "🔧",
    description: type.description || "",
    supports: type.supports || [],
    children: type.children || [],
    parent: type.parent || "",
  }));

  console.log(componentTypes);

  // Create component mutation
  const createComponentMutation = useMutation({
    mutationFn: (componentData: Partial<Component>) => post("/components", componentData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["components"] });
      setIsDialogOpen(false);
      setComponentForm({});
    },
  });

  // Update component mutation
  const updateComponentMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<Component> }) => patch(`/components/${id}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["components"] });
      setIsDialogOpen(false);
      setSelectedComponent(null);
    },
  });

  // Delete component mutation
  const deleteComponentMutation = useMutation({
    mutationFn: (id: string) => del(`/components/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["components"] });
    },
  });
  const handleCreateComponent = () => {
    setIsCreateMode(true);
    setComponentForm({
      type: "accordion",
      title: "",
      description: "",
      context: "default",
      settings: {},
      children: {},
      sort_order: 0,
      is_active: true,
      capability: "manage_options",
    });
    setIsDialogOpen(true);
  };

  const handleEditComponent = (component: Component) => {
    setIsCreateMode(false);
    setSelectedComponent(component);
    setComponentForm(component);
    setIsDialogOpen(true);
  };

  const handleSaveComponent = () => {
    if (isCreateMode) {
      createComponentMutation.mutate(componentForm);
    } else if (selectedComponent) {
      updateComponentMutation.mutate({
        id: selectedComponent.id,
        data: componentForm,
      });
    }
  };

  const handleDeleteComponent = (id: string) => {
    if (confirm("Are you sure you want to delete this component?")) {
      deleteComponentMutation.mutate(id);
    }
  };
  const updateFormField = (field: string, value: any) => {
    setComponentForm((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const updateFormSettings = (key: string, value: any) => {
    setComponentForm((prev) => ({
      ...prev,
      settings: {
        ...prev.settings,
        [key]: value,
      },
    }));
  };

  const getComponentIcon = (type: string) => {
    const icons: Record<string, string> = {
      accordion: "📋",
      tab: "📑",
      row: "▦",
      column: "▨",
    };
    return icons[type] || "🔧";
  };

  if (componentsLoading || typesLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="components-manager">
      <div className="components-header">
        <h3 className="text-lg font-semibold mb-4">Components</h3>
        <Button onClick={handleCreateComponent}>Create Component</Button>
      </div>

      <div className="components-grid">
        <AnimatePresence>
          {components.map((component: Component) => (
            <motion.div
              key={component.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -20 }}
              transition={{ duration: 0.2 }}
              className="component-card"
            >
              {" "}
              <div className="component-card-header">
                <div className="component-icon">{getComponentIcon(component.type)}</div>
                <div className="component-info">
                  <h4 className="component-title">{component.title}</h4>
                  <p className="component-type">{component.type}</p>
                  {component.description && <p className="component-description">{component.description}</p>}
                </div>
              </div>{" "}
              <div className="component-meta">
                <span className="component-context">Context: {component.context || "default"}</span>
                <span className="component-children-count">{Object.keys(component.children || {}).length} children</span>
              </div>
              <div className="component-actions">
                <Button variant="outline" size="sm" onClick={() => handleEditComponent(component)}>
                  Edit
                </Button>
                <Button variant="outline" size="sm" onClick={() => handleDeleteComponent(component.id)}>
                  Delete
                </Button>
              </div>
            </motion.div>
          ))}
        </AnimatePresence>
      </div>

      {components.length === 0 && (
        <div className="empty-state">
          <div className="empty-state-icon">🔧</div>
          <h3 className="empty-state-title">No components yet</h3>
          <p className="empty-state-description">Create your first component to get started.</p>
          <Button onClick={handleCreateComponent}>Create Component</Button>
        </div>
      )}

      {/* Component Form Dialog */}
      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent size="lg">
          <DialogHeader>
            <DialogTitle>{isCreateMode ? "Create Component" : "Edit Component"}</DialogTitle>
          </DialogHeader>

          <div className="component-form">
            <div className="form-grid">
              <FieldRenderer
                config={{
                  id: "title",
                  type: "text",
                  label: "Component Title",
                  required: true,
                  placeholder: "Enter component title",
                }}
                value={componentForm.title || ""}
                onChange={(value) => updateFormField("title", value)}
              />

              <FieldRenderer
                config={{
                  id: "type",
                  type: "select",
                  label: "Component Type",
                  required: true,
                  options: componentTypes.map((type: ComponentType) => ({
                    label: type.name,
                    value: type.id,
                  })),
                }}
                value={componentForm.type || ""}
                onChange={(value) => updateFormField("type", value)}
              />

              <FieldRenderer
                config={{
                  id: "description",
                  type: "textarea",
                  label: "Description",
                  placeholder: "Enter component description",
                  rows: 3,
                }}
                value={componentForm.description || ""}
                onChange={(value) => updateFormField("description", value)}
              />
            </div>
            {/* Component Type Specific Configuration */}
            {componentForm.type && (
              <div className="component-config">
                <h4 className="config-title">Component Configuration</h4>{" "}
                {componentForm.type === "accordion" && (
                  <div className="config-grid">
                    <FieldRenderer
                      config={{
                        id: "collapsed",
                        type: "switcher",
                        label: "Start Collapsed",
                      }}
                      value={componentForm.settings?.collapsed || false}
                      onChange={(value) => updateFormSettings("collapsed", value)}
                    />
                    <FieldRenderer
                      config={{
                        id: "multiple",
                        type: "switcher",
                        label: "Allow Multiple Open",
                      }}
                      value={componentForm.settings?.multiple || false}
                      onChange={(value) => updateFormSettings("multiple", value)}
                    />
                  </div>
                )}
                {componentForm.type === "tab" && (
                  <div className="config-grid">
                    <FieldRenderer
                      config={{
                        id: "orientation",
                        type: "select",
                        label: "Tab Orientation",
                        options: [
                          { label: "Horizontal", value: "horizontal" },
                          { label: "Vertical", value: "vertical" },
                        ],
                      }}
                      value={componentForm.settings?.orientation || "horizontal"}
                      onChange={(value) => updateFormSettings("orientation", value)}
                    />
                    <FieldRenderer
                      config={{
                        id: "icon",
                        type: "text",
                        label: "Tab Icon",
                        placeholder: "Icon class or emoji",
                      }}
                      value={componentForm.settings?.icon || ""}
                      onChange={(value) => updateFormSettings("icon", value)}
                    />
                  </div>
                )}
                {componentForm.type === "row" && (
                  <div className="config-grid">
                    <FieldRenderer
                      config={{
                        id: "gap",
                        type: "select",
                        label: "Column Gap",
                        options: [
                          { label: "Small", value: "sm" },
                          { label: "Medium", value: "md" },
                          { label: "Large", value: "lg" },
                        ],
                      }}
                      value={componentForm.settings?.gap || "md"}
                      onChange={(value) => updateFormSettings("gap", value)}
                    />
                    <FieldRenderer
                      config={{
                        id: "align",
                        type: "select",
                        label: "Vertical Alignment",
                        options: [
                          { label: "Top", value: "start" },
                          { label: "Center", value: "center" },
                          { label: "Bottom", value: "end" },
                        ],
                      }}
                      value={componentForm.settings?.align || "start"}
                      onChange={(value) => updateFormSettings("align", value)}
                    />
                  </div>
                )}{" "}
                {componentForm.type === "column" && (
                  <div className="config-grid">
                    <FieldRenderer
                      config={{
                        id: "width",
                        type: "select",
                        label: "Column Width",
                        options: [
                          { label: "Auto", value: "auto" },
                          { label: "1/12", value: "1/12" },
                          { label: "2/12", value: "2/12" },
                          { label: "3/12", value: "3/12" },
                          { label: "4/12", value: "4/12" },
                          { label: "6/12", value: "6/12" },
                          { label: "8/12", value: "8/12" },
                          { label: "12/12", value: "12/12" },
                        ],
                      }}
                      value={componentForm.settings?.width || "auto"}
                      onChange={(value) => updateFormSettings("width", value)}
                    />
                  </div>
                )}
              </div>
            )}{" "}
            <div className="form-actions">
              <Button variant="outline" onClick={() => setIsDialogOpen(false)}>
                Cancel
              </Button>{" "}
              <Button onClick={handleSaveComponent} disabled={createComponentMutation.isPending || updateComponentMutation.isPending}>
                {isCreateMode ? "Create Component" : "Save Changes"}
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
};
