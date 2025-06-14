import React, { useMemo } from "react";
import { useForm } from "@tanstack/react-form";
import { useQuery } from "@tanstack/react-query";
import { Button } from "../ui/Button";
import { SaveIcon, XIcon } from "@/components/icons";
import { DynamicFieldRenderer, type DynamicField } from "../DynamicFieldRenderer";
import { useAPI } from "@/hooks/useAPI";

// Component - backend/API structure
export interface Component {
  id: string;
  type: string;
  title: string;
  description: string;
  parent_id?: string;
  section_id?: string;
  context: string;
  settings: Record<string, any>;
  children: Record<string, any>;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
}

// ComponentData - frontend form structure
export interface ComponentData {
  id?: string;
  type: string;
  title: string;
  description?: string;
  parent_id?: string;
  section_id?: string;
  context?: string;
  settings?: Record<string, any>;
  children?: Record<string, any>;
  is_active?: boolean;
}

interface ComponentFormProps {
  component?: ComponentData;
  onSave: (component: Component) => void;
  onCancel: () => void;
  error?: string;
  isLoading?: boolean;
}

interface ComponentType {
  id: string;
  name: string;
  type: string;
  description: string;
  category: string;
  supports: string[];
  children?: string[];
  parent?: string;
}

// Convert ComponentData to Component format (for API submission)
const convertComponentDataToComponent = (componentData: ComponentData, dynamicFields?: DynamicField[]): Component => {
  const { id, type, title, description, parent_id, section_id, context, settings, children, is_active, ...rest } = componentData;

  // Base component data
  const component: Component = {
    id: id || "new",
    type,
    title,
    description: description || "",
    parent_id,
    section_id,
    context: context || "default",
    settings: {},
    children: children || {},
    is_active: is_active ?? true,
  };

  // Include existing settings first
  if (settings) {
    component.settings = {
      ...settings,
    };
  }

  // Move dynamic field values to settings (only non-core properties)
  if (dynamicFields) {
    dynamicFields.forEach((dynamicField) => {
      const fieldValue = (componentData as any)[dynamicField.id];
      // Only add to settings if it's not a core Component property
      const coreProperties = ["id", "type", "title", "description", "parent_id", "section_id", "context", "children", "is_active"];
      if (fieldValue !== undefined && fieldValue !== null && fieldValue !== "" && !coreProperties.includes(dynamicField.id)) {
        component.settings[dynamicField.id] = fieldValue;
      }
    });
  }
  // Handle any remaining fields that aren't core properties or in dynamicFields
  Object.keys(rest).forEach((key) => {
    const value = (rest as any)[key];
    const coreProperties = ["id", "type", "title", "description", "parent_id", "section_id", "context", "children", "is_active"];
    if (value !== undefined && value !== null && value !== "" && !coreProperties.includes(key)) {
      component.settings[key] = value;
    }
  });

  return component;
};

// Convert Component to ComponentData format (for form editing)
export const convertComponentToComponentData = (component: Component | null): ComponentData | undefined => {
  if (!component) return undefined;

  return {
    id: component.id,
    type: component.type,
    title: component.title,
    description: component.description,
    parent_id: component.parent_id,
    section_id: component.section_id,
    context: component.context,
    settings: {
      ...component.settings, // Include all settings
    },
    children: component.children,
    is_active: component.is_active,
  };
};

export const ComponentForm: React.FC<ComponentFormProps> = ({ component, onSave, onCancel, error, isLoading = false }) => {
  const { get } = useAPI();

  // Fetch component types from the backend
  const { data: componentTypesResponse = {}, isLoading: isLoadingComponentTypes } = useQuery({
    queryKey: ["component-types"],
    queryFn: () => get("/component-types"),
  });

  // Extract component types from the response
  const componentTypesData = Object.entries(componentTypesResponse.component_types || {}).map(([id, type]: [string, any]) => ({
    id,
    name: type.name || type.class_name?.split("\\").pop() || id,
    type: id,
    class_name: type.class_name || "",
    category: type.category || "general",
    description: type.description || "",
    supports: type.supports || [],
    children: type.children || [],
    parent: type.parent || "",
  }));

  console.log("Component", component);
  const form = useForm({
    defaultValues: {
      id: component?.id || "new",
      type: component?.type || "",
      title: component?.title || "",
      description: component?.description || "",
      parent_id: component?.parent_id || "",
      section_id: component?.section_id || "",
      context: component?.context || "default",
      is_active: component?.is_active ?? true,
      // Include all settings values as default form values
      ...(component?.settings || {}),
    },
    onSubmit: async ({ value }) => {
      // Validate required fields
      if (!value.title.trim()) {
        form.setFieldMeta("title", (prev) => ({
          ...prev,
          errors: ["Title is required"],
        }));
        return;
      }

      if (!value.type) {
        form.setFieldMeta("type", (prev) => ({
          ...prev,
          errors: ["Component type is required"],
        }));
        return;
      }

      // Convert form data to proper component format
      const convertedFormData = convertComponentDataToComponent(value, dynamicFields);

      onSave(convertedFormData);
    },
  });

  // Fetch component type configuration when type is selected
  const [selectedType, setSelectedType] = React.useState(component?.type || "");
  const { data: componentTypeConfig } = useQuery({
    queryKey: ["component-type-config", selectedType],
    queryFn: () => get(`/component-types/${selectedType}/config`),
    enabled: !!selectedType,
  });

  // Get selected component type details
  const selectedComponentType = useMemo(() => {
    if (!selectedType) return null;
    return componentTypesData.find((ct: ComponentType) => ct.type === selectedType) || null;
  }, [componentTypesData, selectedType]);

  // Dynamic fields based on component type configuration from backend
  const dynamicFields = useMemo((): DynamicField[] => {
    if (!componentTypeConfig?.config_fields) return [];

    // Convert backend config fields to DynamicField format
    return componentTypeConfig.config_fields.map((configField: any) => {
      // Priority order for field values:
      // 1. Current form value (for live updates)
      // 2. Component settings value (for editing existing components)
      // 3. Config field default value
      // 4. Empty string fallback

      let fieldValue = form.getFieldValue(configField.id);

      // If no form value exists, check component settings
      if ((fieldValue === undefined || fieldValue === null || fieldValue === "") && component?.settings) {
        fieldValue = component.settings[configField.id];
      }

      // Fallback to config default if still no value
      if (fieldValue === undefined || fieldValue === null || fieldValue === "") {
        fieldValue = configField.default || "";
      }

      return {
        id: configField.id,
        name: configField.name || configField.id,
        type: configField.type,
        title: configField.title,
        description: configField.description,
        required: configField.required || false,
        placeholder: configField.placeholder,
        rows: configField.rows,
        min: configField.min,
        max: configField.max,
        step: configField.step,
        options: configField.options,
        value: fieldValue,
        validation: configField.validation,
        context: configField.context || "default",
      };
    });
  }, [componentTypeConfig, form, component]);

  console.log(dynamicFields, "Dynamic Fields Configured");

  // Create component type selection field
  const typeField: DynamicField = {
    id: "type",
    type: "select",
    title: "Component Type",
    placeholder: "Select a component type",
    description: "Select the type of component to create",
    value: selectedType,
    required: true,
    options: componentTypesData.reduce((acc: Record<string, string>, componentType: ComponentType) => {
      acc[componentType.type] = componentType.name;
      return acc;
    }, {}),
  };

  return (
    <div className="space-y-4">
      {error && (
        <div className="p-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded-md">
          <strong>Error:</strong> {error}
        </div>
      )}

      <form
        onSubmit={(e) => {
          e.preventDefault();
          e.stopPropagation();
          void form.handleSubmit();
        }}
        className="space-y-4"
      >
        {/* Component Title */}
        <DynamicFieldRenderer
          key="title"
          field={{
            id: "title",
            type: "text",
            title: "Component Title",
            description: "The display title for this component",
            value: form.getFieldValue("title") || "",
            required: true,
            placeholder: "Enter component title",
          }}
          formApi={form}
          validationRules={{
            required: true,
          }}
        />

        {/* Component Type Selection */}
        {isLoadingComponentTypes ? (
          <div className="p-4 text-center text-gray-500">Loading component types...</div>
        ) : (
          <div className="space-y-2">
            <DynamicFieldRenderer
              key={typeField.id}
              field={typeField}
              formApi={form}
              validationRules={{
                required: typeField.required,
                ...typeField.validation,
              }}
              onChange={(value) => {
                form.setFieldValue("type", value);
                setSelectedType(value);
                // Clear existing dynamic field values when type changes
                if (component?.settings) {
                  Object.keys(component.settings).forEach((key) => {
                    form.setFieldValue(key as any, "");
                  });
                }
              }}
            />
          </div>
        )}

        {/* Description */}
        <DynamicFieldRenderer
          key="description"
          field={{
            id: "description",
            type: "textarea",
            title: "Description",
            description: "Optional description for this component",
            value: form.getFieldValue("description") || "",
            placeholder: "Enter component description",
            rows: 3,
          }}
          formApi={form}
          validationRules={{}}
        />

        {/* Context */}
        <DynamicFieldRenderer
          key="context"
          field={{
            id: "context",
            type: "select",
            title: "Context",
            description: "Where this component will be used",
            value: form.getFieldValue("context") || "default",
            options: {
              default: "Default",
              post_edit: "Post Edit",
              user_profile: "User Profile",
              product_edit: "Product Edit",
              admin: "Admin",
            },
          }}
          formApi={form}
          validationRules={{}}
        />

        {/* Dynamic Fields - Only show when type is selected and config is loaded */}
        {selectedComponentType && componentTypeConfig && (
          <div className="space-y-4 border-t pt-4">
            <h3 className="text-sm font-medium text-gray-700">{selectedComponentType.name} Configuration</h3>

            {dynamicFields.map((dynamicField) => (
              <DynamicFieldRenderer
                key={dynamicField.id}
                field={dynamicField}
                formApi={form}
                validationRules={{
                  required: dynamicField.required,
                  ...dynamicField.validation,
                }}
              />
            ))}
          </div>
        )}

        {/* Active Status */}
        <DynamicFieldRenderer
          key="is_active"
          field={{
            id: "is_active",
            type: "checkbox",
            title: "Active",
            description: "Whether this component is active and available for use",
            value: form.getFieldValue("is_active") ?? true,
          }}
          formApi={form}
          validationRules={{}}
        />

        <div className="flex justify-end space-x-2 pt-4 border-t border-gray-200">
          <Button type="button" variant="secondary" onClick={onCancel} className="flex items-center space-x-2" disabled={isLoading}>
            <XIcon size={16} />
            <span>Cancel</span>
          </Button>

          <form.Subscribe selector={(state) => [state.canSubmit, state.isSubmitting]}>
            {([canSubmit, isSubmitting]) => (
              <Button
                type="submit"
                variant="primary"
                disabled={!canSubmit || isSubmitting || isLoading}
                className="flex items-center space-x-2"
              >
                {isSubmitting || isLoading ? (
                  <>
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                    <span>Saving...</span>
                  </>
                ) : (
                  <>
                    <SaveIcon size={16} />
                    <span>Save Component</span>
                  </>
                )}
              </Button>
            )}
          </form.Subscribe>
        </div>
      </form>
    </div>
  );
};
