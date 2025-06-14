import React, { useMemo } from "react";
import { useForm } from "@tanstack/react-form";
import { Button } from "../ui/Button";
import { SaveIcon, XIcon } from "@/components/icons";
import { DynamicFieldRenderer, type DynamicField } from "../DynamicFieldRenderer";
import { doAction, applyFilters } from "@/hooks/createHooks";

// SectionTypeData - used in SectionTypeForm (frontend form structure)
export interface SectionTypeData {
  id: string;
  type: string;
  name: string;
  class_name?: string;
  description?: string;
  icon?: string;
  supports?: string[];
  category?: string;
  is_active?: boolean;
}

interface SectionTypeFormProps {
  sectionType?: SectionTypeData;
  onSave: (sectionType: SectionTypeData) => void;
  onCancel: () => void;
  error?: string;
  isLoading?: boolean;
}

export const SectionTypeForm: React.FC<SectionTypeFormProps> = ({ sectionType, onSave, onCancel, error, isLoading = false }) => {
  const form = useForm({
    defaultValues: {
      id: sectionType?.id || "new",
      type: sectionType?.type || "",
      name: sectionType?.name || "",
      class_name: sectionType?.class_name || "",
      description: sectionType?.description || "",
      icon: sectionType?.icon || "",
      supports: sectionType?.supports || [],
      category: sectionType?.category || "general",
      is_active: sectionType?.is_active ?? true,
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

      // Generate ID for new section types
      const formData: SectionTypeData = {
        ...value,
        id: value.id || "new",
        supports: Array.isArray(value.supports) ? value.supports : [],
      };

      // Apply filters for extensibility
      const processedSectionType = applyFilters("spiderBoxes.sectionTypeToSave", formData, sectionType) as SectionTypeData;

      // Fire action for extensibility
      doAction("spiderBoxes.beforeSectionTypeSave", processedSectionType, sectionType);

      onSave(processedSectionType);
    },
  });

  // Define the form fields
  const formFields = useMemo((): DynamicField[] => {
    return [
      {
        id: "type",
        type: "text",
        title: "Section Type",
        description: "Unique identifier for the section type (e.g., section, form, meta-box)",
        required: true,
        placeholder: "e.g., section, form, meta-box",
        value: form.getFieldValue("type") || "",
      },
      {
        id: "name",
        type: "text",
        title: "Display Name",
        description: "Human-readable name for the section type",
        required: true,
        placeholder: "e.g., Basic Section, Form Section",
        value: form.getFieldValue("name") || "",
      },
      {
        id: "class_name",
        type: "text",
        title: "PHP Class Name",
        description: "Fully qualified PHP class name for the section",
        placeholder: "e.g., SpiderBoxes\\Sections\\SectionType",
        value: form.getFieldValue("class_name") || "",
      },
      {
        id: "description",
        type: "textarea",
        title: "Description",
        description: "Brief description of what this section type does",
        placeholder: "Describe what this section type is used for...",
        rows: 3,
        value: form.getFieldValue("description") || "",
      },
      {
        id: "icon",
        type: "text",
        title: "Icon",
        description: "Icon class or emoji to represent this section type",
        placeholder: "📄 or icon-class-name",
        value: form.getFieldValue("icon") || "",
      },
      {
        id: "supports",
        type: "tags",
        title: "Supported Features",
        description: "Features this section type supports (e.g., title, description, components). Press Enter or comma to add tags.",
        placeholder: "title, description, components, collapsible, action",
        value: form.getFieldValue("supports") || [],
      },
      {
        id: "category",
        type: "select",
        title: "Category",
        description: "Category to group related section types",
        value: form.getFieldValue("category") || "general",
        options: {
          general: "General",
          layout: "Layout",
          form: "Form",
          content: "Content",
          advanced: "Advanced",
        },
      },
      {
        id: "is_active",
        type: "checkbox",
        title: "Active",
        description: "Whether this section type is active and available for use",
        value: form.getFieldValue("is_active") ?? true,
      },
    ];
  }, [form]);

  return (
    <div className="section-type-form">
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
                    <span>Save Section Type</span>
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
