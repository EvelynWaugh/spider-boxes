import React, { useMemo } from "react";
import { useForm } from "@tanstack/react-form";
import { useQuery } from "@tanstack/react-query";
import { Button } from "../ui/Button";
import { SaveIcon, XIcon } from "@/components/icons";
import { DynamicFieldRenderer, DynamicField } from "../DynamicFieldRenderer";
import { useAPI } from "@/hooks/useAPI";

export interface FieldData {
  id?: string;
  name?: string;
  type: string;
  label: string;
  description?: string;
  required?: boolean;
  default_value?: any;
  options?: any;
}

interface FieldFormProps {
  field?: FieldData;
  onSave: (field: FieldData) => void;
  onCancel: () => void;
  error?: string;
  isLoading?: boolean;
}

interface FieldType {
  id: string;
  name: string;
  type: string;
  description: string;
  category: string;
  supports: string[];
}

export const FieldForm: React.FC<FieldFormProps> = ({ field, onSave, onCancel, error, isLoading = false }) => {
  const { get } = useAPI();

  // Generate a unique ID for new fields
  const generateFieldId = (label: string): string => {
    const baseId = label
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "_")
      .replace(/^_+|_+$/g, "");
    const timestamp = Date.now().toString(36);
    return `field_${baseId}_${timestamp}`;
  }; // Fetch field types from the backend
  const { data: fieldTypesData = [], isLoading: isLoadingFieldTypes } = useQuery({
    queryKey: ["field-types"],
    queryFn: () => get("/field-types"),
  });

  const form = useForm({
    defaultValues: {
      id: field?.id || "new",
      name: field?.name || "",
      type: field?.type || "",
      label: field?.label || "",
      description: field?.description || "",
      required: field?.required || false,
      default_value: field?.default_value || "",
      options: field?.options || {},
    },
    onSubmit: async ({ value }) => {
      // Validate required fields
      if (!value.label.trim()) {
        form.setFieldMeta("label", (prev) => ({
          ...prev,
          errors: ["Label is required"],
        }));
        return;
      }

      if (!value.type) {
        form.setFieldMeta("type", (prev) => ({
          ...prev,
          errors: ["Field type is required"],
        }));
        return;
      }

      // Generate ID for new fields
      const formData: FieldData = {
        ...value,
        // Generate ID if this is a new field, otherwise keep existing ID
        id: value.id || "new",
      };

      onSave(formData);
    },
  }); // Fetch field type configuration when type is selected
  const [selectedType, setSelectedType] = React.useState(field?.type || "");
  const { data: fieldTypeConfig } = useQuery({
    queryKey: ["field-type-config", selectedType],
    queryFn: () => get(`/field-types/${selectedType}/config`),
    enabled: !!selectedType,
  });
  // Get selected field type details
  const selectedFieldType = useMemo(() => {
    if (!selectedType) return null;
    return fieldTypesData.find((ft: FieldType) => ft.type === selectedType) || null;
  }, [fieldTypesData, selectedType]);

  // Dynamic fields based on field type configuration from backend
  const dynamicFields = useMemo((): DynamicField[] => {
    if (!fieldTypeConfig?.config_fields) return [];

    // Convert backend config fields to DynamicField format
    return fieldTypeConfig.config_fields.map((configField: any) => ({
      id: configField.id,
      name: configField.name || configField.id, // Use name if available, otherwise fallback to id
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
      value: form.getFieldValue(configField.id) || configField.default || "",
      validation: configField.validation,
    }));
  }, [fieldTypeConfig, form]);
  // Create field type selection field
  const typeField: DynamicField = {
    id: "type",
    type: "select",
    title: "Field Type",
    description: "Select the type of field to create",
    value: selectedType,
    required: true,
    options: fieldTypesData.reduce((acc: Record<string, any>, fieldType: FieldType) => {
      acc[fieldType.type] = {
        label: fieldType.name,
        value: fieldType.type,
      };
      return acc;
    }, {}),
  };
  // Handle field changes
  const handleFieldChange = (fieldId: string, value: any) => {
    // Update form field value
    if (fieldId === "type") {
      form.setFieldValue("type", value);
      setSelectedType(value);
      form.setFieldValue("options", {});
    } else if (fieldId === "label") {
      form.setFieldValue("label", value);
    } else if (fieldId === "description") {
      form.setFieldValue("description", value);
    } else if (fieldId === "required") {
      form.setFieldValue("required", value);
    } else if (fieldId === "default_value") {
      form.setFieldValue("default_value", value);
    } else if (fieldId === "options") {
      form.setFieldValue("options", value);
    } else {
      // For dynamic fields, store in options object
      const currentOptions = form.getFieldValue("options") || {};
      form.setFieldValue("options", {
        ...currentOptions,
        [fieldId]: value,
      });
    }
  };

  const requiredNameField: DynamicField = {
    id: "name",
    type: "text",
    title: "Field Name",
    description: "The name of the field, used for saving data.",
    value: field?.name || "",
    required: true,
    validation: {
      pattern: /^[a-z0-9_]+$/,
    },
  };

  const metaField: DynamicField = {
    id: "meta_field",
    type: "checkbox",
    title: "Is Meta Field",
    description: "Check if this field is a meta field.",
    value: false,
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
        {/* Field Name */}
        <DynamicFieldRenderer
          key={requiredNameField.id}
          field={requiredNameField}
          formApi={form}
          validationRules={{
            required: requiredNameField.required,
            ...requiredNameField.validation,
          }}
        />
        {/* Field Type Selection */}
        {isLoadingFieldTypes ? (
          <div className="p-4 text-center text-gray-500">Loading field types...</div>
        ) : (
          <div className="space-y-2">
            <label className="block text-sm font-medium text-gray-700">{typeField.title}</label>
            {typeField.description && <p className="text-sm text-gray-500">{typeField.description}</p>}
            <select
              value={selectedType}
              onChange={(e) => handleFieldChange("type", e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              required={typeField.required}
            >
              <option value="">Select field type...</option>
              {Object.entries(typeField.options || {}).map(([key, option]: [string, any]) => (
                <option key={key} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </div>
        )}

        {/* Dynamic Fields - Only show when type is selected and config is loaded */}
        {selectedFieldType && fieldTypeConfig && (
          <div className="space-y-4 border-t pt-4">
            <h3 className="text-sm font-medium text-gray-700">{selectedFieldType.name} Configuration</h3>

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
                disabled={!canSubmit || isSubmitting || isLoading || !selectedFieldType}
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
                    <span>Save Field</span>
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
