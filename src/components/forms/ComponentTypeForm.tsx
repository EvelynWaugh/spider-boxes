import React, { useMemo } from "react";
import { useForm } from "@tanstack/react-form";
import { Button } from "../ui/Button";
import { SaveIcon, XIcon } from "@/components/icons";
import { DynamicFieldRenderer, type DynamicField } from "../DynamicFieldRenderer";

// ComponentTypeData - used in ComponentTypeForm (frontend form structure)
export interface ComponentTypeData {
  id: string;
  type: string;
  name?: string;
  class_name?: string;
  description?: string;
  icon?: string;
  supports?: string[];
  children?: string[];
  parent?: string;
  category?: string;
  is_active?: boolean;
}

interface ComponentTypeFormProps {
  componentType?: ComponentTypeData;
  onSave: (componentType: ComponentTypeData) => void;
  onCancel: () => void;
  error?: string;
  isLoading?: boolean;
}

export const ComponentTypeForm: React.FC<ComponentTypeFormProps> = ({ componentType, onSave, onCancel, error, isLoading = false }) => {
  const form = useForm({
    defaultValues: {
      id: componentType?.id || "new",
      type: componentType?.type || "",
      name: componentType?.name || "",
      class_name: componentType?.class_name || "",
      description: componentType?.description || "",
      icon: componentType?.icon || "",
      supports: componentType?.supports || [],
      children: componentType?.children || [],
      parent: componentType?.parent || "",
      category: componentType?.category || "layout",
      is_active: componentType?.is_active ?? true,
    },
    onSubmit: async ({ value }) => {
      // Validate required fields
      if (!value.type.trim()) {
        form.setFieldMeta("type", (prev) => ({
          ...prev,
          errors: ["Type is required"],
        }));
        return;
      }

      if (!value.name.trim()) {
        form.setFieldMeta("name", (prev) => ({
          ...prev,
          errors: ["Name is required"],
        }));
        return;
      }

      // Generate ID for new component types
      const formData: ComponentTypeData = {
        ...value,
        id: value.id || "new",
        supports: Array.isArray(value.supports) ? value.supports : [],
        children: Array.isArray(value.children) ? value.children : [],
      };

      onSave(formData);
    },
  });

  // Define the form fields
  const formFields = useMemo((): DynamicField[] => {
    return [
      {
        id: "type",
        type: "text",
        title: "Component Type",
        description: "Unique identifier for the component type (e.g., accordion, tab, row)",
        required: true,
        placeholder: "e.g., accordion, tab, row",
        value: form.getFieldValue("type") || "",
      },
      {
        id: "name",
        type: "text",
        title: "Display Name",
        description: "Human-readable name for the component type",
        required: true,
        placeholder: "e.g., Accordion, Tab, Row",
        value: form.getFieldValue("name") || "",
      },
      {
        id: "class_name",
        type: "text",
        title: "PHP Class Name",
        description: "Fully qualified PHP class name for the component",
        placeholder: "e.g., SpiderBoxes\\Components\\AccordionComponent",
        value: form.getFieldValue("class_name") || "",
      },
      {
        id: "description",
        type: "textarea",
        title: "Description",
        description: "Brief description of what this component type does",
        placeholder: "Describe the purpose and functionality of this component type",
        rows: 3,
        value: form.getFieldValue("description") || "",
      },
      {
        id: "icon",
        type: "text",
        title: "Icon",
        description: "Icon class or emoji to represent this component type",
        placeholder: "📋 or icon-class-name",
        value: form.getFieldValue("icon") || "",
      },
      {
        id: "category",
        type: "select",
        title: "Category",
        description: "Category to group this component type",
        placeholder: "Select a category",
        value: form.getFieldValue("category") || "layout",
        options: {
          layout: "Layout",
          content: "Content",
          form: "Form",
          media: "Media",
          advanced: "Advanced",
        },
      },
      {
        id: "supports",
        type: "tags",
        title: "Supported Features",
        description: "Features this component type supports (e.g., title, description, fields). Press Enter or comma to add tags.",
        placeholder: "title, description, fields, collapsed",
        value: form.getFieldValue("supports") || [],
      },
      {
        id: "children",
        type: "tags",
        title: "Allowed Children",
        description: "Component types that can be children of this component. Press Enter or comma to add tags.",
        placeholder: "pane, tab, column",
        value: form.getFieldValue("children") || [],
      },
      {
        id: "parent",
        type: "text",
        title: "Parent Type",
        description: "If this is a child component, specify the parent component type",
        placeholder: "e.g., accordion, tabs, row",
        value: form.getFieldValue("parent") || "",
      },
      {
        id: "is_active",
        type: "checkbox",
        title: "Active",
        description: "Whether this component type is active and available for use",
        value: form.getFieldValue("is_active") ?? true,
      },
    ];
  }, [form]);

  return (
    <div className="component-type-form">
      {error && (
        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
          <div className="text-sm text-red-600">{error}</div>
        </div>
      )}

      <form
        onSubmit={(e) => {
          e.preventDefault();
          e.stopPropagation();
          form.handleSubmit();
        }}
        className="space-y-6"
      >
        {formFields.map((field) => (
          <DynamicFieldRenderer
            key={field.id}
            field={field}
            formApi={form}
            validationRules={{
              required: field.required,
              ...field.validation,
            }}
          />
        ))}

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
                    <span>Save Component Type</span>
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
