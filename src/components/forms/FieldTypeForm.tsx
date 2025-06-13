import React, { useMemo } from "react";
import { useForm } from "@tanstack/react-form";
import { Button } from "../ui/Button";
import { SaveIcon, XIcon } from "@/components/icons";
import { DynamicFieldRenderer, type DynamicField } from "../DynamicFieldRenderer";

// FieldTypeData - used in FieldTypeForm (frontend form structure)
export interface FieldTypeData {
  id: string;
  type: string;
  description?: string;
  icon?: string;
  supports?: string[];
  is_active?: boolean;
}

interface FieldTypeFormProps {
  fieldType?: FieldTypeData;
  onSave: (fieldType: FieldTypeData) => void;
  onCancel: () => void;
  error?: string;
  isLoading?: boolean;
}

export const FieldTypeForm: React.FC<FieldTypeFormProps> = ({ fieldType, onSave, onCancel, error, isLoading = false }) => {
  const form = useForm({
    defaultValues: {
      id: fieldType?.id || "new",
      type: fieldType?.type || "",
      description: fieldType?.description || "",
      icon: fieldType?.icon || "",
      supports: fieldType?.supports || [],
      is_active: fieldType?.is_active ?? true,
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

      // Generate ID for new field types
      const formData: FieldTypeData = {
        ...value,
        id: value.id || "new",
        supports: Array.isArray(value.supports) ? value.supports : [],
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
        title: "Field Type",
        description: "Unique",
        required: true,
        placeholder: "e.g., text, number, select",
        value: form.getFieldValue("type") || "",
      },

      {
        id: "description",
        type: "textarea",
        title: "Description",
        description: "Brief description of what this field type does",
        placeholder: "Describe the purpose and functionality of this field type",
        rows: 3,
        value: form.getFieldValue("description") || "",
      },
      {
        id: "icon",
        type: "text",
        title: "Icon",
        description: "Icon class or emoji to represent this field type",
        placeholder: "📝 or icon-class-name",
        value: form.getFieldValue("icon") || "",
      },
      {
        id: "supports",
        type: "tags",
        title: "Supported Features",
        description:
          "Features this field type supports (e.g., label, description, placeholder, validation). Press Enter or comma to add tags.",
        placeholder: "label, description, placeholder, required, validation",
        value: form.getFieldValue("supports") || [],
      },

      {
        id: "is_active",
        type: "checkbox",
        title: "Active",
        description: "Whether this field type is active and available for use",
        value: form.getFieldValue("is_active") ?? true,
      },
    ];
  }, [form]);

  return (
    <div className="field-type-form">
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
                    <span>Save Field Type</span>
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
