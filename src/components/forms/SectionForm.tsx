import React, { useMemo } from "react";
import { useForm } from "@tanstack/react-form";
import { useQuery } from "@tanstack/react-query";
import { Button } from "../ui/Button";
import { SaveIcon, XIcon } from "@/components/icons";
import { DynamicFieldRenderer, type DynamicField } from "../DynamicFieldRenderer";
import { useAPI } from "@/hooks/useAPI";
import { doAction, applyFilters } from "@/hooks/createHooks";
import { Section, SectionData } from "@/utils/section";

interface SectionFormProps {
  section?: SectionData;
  onSave: (section: Section) => void;
  onCancel: () => void;
  error?: string;
  isLoading?: boolean;
}

interface SectionType {
  id: string;
  name: string;
  type: string;
  description: string;
  category: string;
  supports: string[];
}

// Convert form data to Section format (for API submission)
const convertFormDataToSection = (formData: any, dynamicFields?: DynamicField[]): Section => {
  const { id, type, title, description, context, screen, components, is_active, ...rest } = formData;

  // Base section data
  const section: Section = {
    id: id === "new" ? "" : id,
    type,
    title,
    description: description || "",
    context: context || "default",
    screen: screen || "",
    settings: {},
    components: components || {},
    is_active: is_active ?? true,
  };

  // Move dynamic field values to settings (only non-core properties)
  if (dynamicFields) {
    dynamicFields.forEach((dynamicField) => {
      const fieldValue = formData[dynamicField.id];
      // Only add to settings if it's not a core Section property
      const coreProperties = ["id", "type", "title", "description", "context", "screen", "components", "is_active"];
      if (fieldValue !== undefined && fieldValue !== null && fieldValue !== "" && !coreProperties.includes(dynamicField.id)) {
        section.settings[dynamicField.id] = fieldValue;
      }
    });
  }

  // Handle any remaining fields that aren't core properties or in dynamicFields
  Object.keys(rest).forEach((key) => {
    const value = rest[key];
    const coreProperties = ["id", "type", "title", "description", "context", "screen", "components", "is_active"];
    if (value !== undefined && value !== null && value !== "" && !coreProperties.includes(key)) {
      section.settings[key] = value;
    }
  });

  return section;
};

// Convert Section to SectionData format (for form editing)
export const convertSectionToSectionData = (section: Section | null): SectionData | undefined => {
  if (!section) return undefined;

  return {
    id: section.id,
    type: section.type,
    title: section.title,
    description: section.description,
    context: section.context,
    screen: section.screen,
    settings: {
      ...section.settings, // Include all settings
    },
    components: section.components,
    is_active: section.is_active,
  };
};

export const SectionForm: React.FC<SectionFormProps> = ({ section, onSave, onCancel, error, isLoading = false }) => {
  const { get } = useAPI();

  // Fetch section types from the backend
  const { data: sectionTypesResponse = {}, isLoading: isLoadingSectionTypes } = useQuery({
    queryKey: ["section-types"],
    queryFn: () => get("/section-types"),
  });

  // Extract section types from the response
  const sectionTypesData = Object.entries(sectionTypesResponse.section_types || {}).map(([id, type]: [string, any]) => ({
    id,
    name: type.name || type.class_name?.split("\\").pop() || id,
    type: id,
    class_name: type.class_name || "",
    category: type.category || "general",
    description: type.description || "",
    supports: type.supports || [],
  }));

  console.log("Section", section);
  const form = useForm({
    defaultValues: {
      id: section?.id || "new",
      type: section?.type || "",
      title: section?.title || "",
      description: section?.description || "",
      context: section?.context || "default",
      screen: section?.screen || "",
      is_active: section?.is_active ?? true,
      // Include all settings values as default form values
      ...(section?.settings || {}),
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
          errors: ["Section type is required"],
        }));
        return;
      }

      // Convert form data to proper section format
      const convertedFormData = convertFormDataToSection(value, dynamicFields);

      // Apply filters for extensibility
      const processedSection = applyFilters("spiderBoxes.sectionToSave", convertedFormData, section) as Section;

      // Fire action for extensibility
      doAction("spiderBoxes.beforeSectionSave", processedSection, section);

      onSave(processedSection);
    },
  });

  // Fetch section type configuration when type is selected
  const [selectedType, setSelectedType] = React.useState(section?.type || "");
  const { data: sectionTypeConfig } = useQuery({
    queryKey: ["section-type-config", selectedType],
    queryFn: () => get(`/section-types/${selectedType}/config`),
    enabled: !!selectedType,
  });

  // Get selected section type details
  const selectedSectionType = useMemo(() => {
    if (!selectedType) return null;
    return sectionTypesData.find((st: SectionType) => st.type === selectedType) || null;
  }, [sectionTypesData, selectedType]);

  // Dynamic fields based on section type configuration from backend
  const dynamicFields = useMemo((): DynamicField[] => {
    if (!sectionTypeConfig?.config_fields) return [];

    // Convert backend config fields to DynamicField format
    return sectionTypeConfig.config_fields.map((configField: any) => {
      // Priority order for field values:
      // 1. Current form value (for live updates)
      // 2. Section settings value (for editing existing sections)
      // 3. Config field default value
      // 4. Empty string fallback

      let fieldValue = form.getFieldValue(configField.id);

      // If no form value exists, check section settings
      if ((fieldValue === undefined || fieldValue === null || fieldValue === "") && section?.settings) {
        fieldValue = section.settings[configField.id];
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
  }, [sectionTypeConfig, form, section]);

  console.log(dynamicFields, "Dynamic Fields Configured");

  // Create section type selection field
  const typeField: DynamicField = {
    id: "type",
    type: "select",
    title: "Section Type",
    description: "Select the type of section",
    value: form.getFieldValue("type") || "",
    required: true,
    options: sectionTypesData.reduce(
      (acc, sectionType) => {
        acc[sectionType.type] = sectionType.name;
        return acc;
      },
      {} as Record<string, any>,
    ),
  };

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        e.stopPropagation();
        form.handleSubmit();
      }}
      className="space-y-6"
    >
      {error && <div className="p-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-md">{error}</div>}

      <div className="space-y-4">
        {/* Title */}
        <DynamicFieldRenderer
          key="title"
          field={{
            id: "title",
            type: "text",
            title: "Section Title",
            description: "Enter a descriptive title for this section",
            value: form.getFieldValue("title") || "",
            required: true,
            placeholder: "Enter section title",
          }}
          formApi={form}
          validationRules={{
            required: true,
          }}
        />

        {/* Section Type Selection */}
        {isLoadingSectionTypes ? (
          <div className="p-4 text-center text-gray-500">Loading section types...</div>
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
                if (section?.settings) {
                  Object.keys(section.settings).forEach((key) => {
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
            description: "Optional description for this section",
            value: form.getFieldValue("description") || "",
            placeholder: "Enter section description",
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
            description: "Where this section will be used",
            value: form.getFieldValue("context") || "default",
            options: {
              default: "Default",
              post_edit: "Post Edit",
              user_profile: "User Profile",
              settings: "Settings",
              wc_product: "WooCommerce Product",
            },
          }}
          formApi={form}
          validationRules={{}}
        />

        {/* Screen */}
        <DynamicFieldRenderer
          key="screen"
          field={{
            id: "screen",
            type: "text",
            title: "Screen",
            description: "Specific screen or page where this section appears",
            value: form.getFieldValue("screen") || "",
            placeholder: "e.g., edit-post, user-edit, product-data",
          }}
          formApi={form}
          validationRules={{}}
        />

        {/* Dynamic Fields based on selected section type */}
        {selectedType && dynamicFields.length > 0 && (
          <div className="space-y-4 p-4 bg-gray-50 rounded-lg">
            <h3 className="text-sm font-medium text-gray-900">{selectedSectionType?.name} Configuration</h3>
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
            description: "Whether this section is active and available for use",
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
                    <span>Save Section</span>
                  </>
                )}
              </Button>
            )}
          </form.Subscribe>
        </div>
      </div>
    </form>
  );
};
