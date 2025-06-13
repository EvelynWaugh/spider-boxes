import type { DynamicField } from "@/components/DynamicFieldRenderer";

// Field - backend/API structure
export interface Field {
  id: string;
  name: string;
  type: string;
  title: string;
  description: string;
  value: any;
  settings: Record<string, any>;
  context: string;
}

// FieldData - frontend form structure
export interface FieldData {
  id?: string;
  name?: string;
  type: string;
  label: string;
  description?: string;
  required?: boolean;
  default_value?: any;
  settings?: Record<string, any>;
  options?: any;
  context?: string;
}

/**
 * Convert Field to FieldData format (for form editing)
 */
export const convertFieldToFieldData = (field: Field | null): FieldData | undefined => {
  if (!field) return undefined;

  return {
    id: field.id,
    name: field.name,
    type: field.type,
    label: field.title,
    description: field.description,
    default_value: field.value,
    settings: {
      meta_field: field.settings.meta_field,
      placeholder: field.settings.placeholder,
      validation: field.settings.validation,
      condition: field.settings.condition,
      ...field.settings, // Include any additional settings
    },
    context: field.context,
  };
};

/**
 * Convert FieldData to Field format (for API submission)
 */
export const convertFieldDataToField = (fieldData: FieldData, configFields?: DynamicField[]): Field => {
  const { id, name, type, label, description, required, default_value, context, options, settings, ...rest } = fieldData;

  // Base field data
  const field: Field = {
    id: id || "new",
    name: name || "",
    type,
    title: label,
    description: description || "",
    value: default_value,
    settings: {},
    context: context || "default",
  };

  // Include existing settings first
  if (settings) {
    field.settings = {
      ...settings,
    };
  }

  // Handle required field - this goes in settings
  if (required !== undefined) {
    field.settings.required = required;
  }

  // Handle options - this goes in settings
  if (options) {
    field.settings.options = options;
  }

  // Move dynamic field values to settings (only non-core properties)
  if (configFields) {
    configFields.forEach((configField) => {
      const fieldValue = (fieldData as any)[configField.id];
      // Only add to settings if it's not a core Field property
      const coreProperties = ["id", "name", "type", "title", "description", "value", "context"];
      if (fieldValue !== undefined && fieldValue !== null && fieldValue !== "" && !coreProperties.includes(configField.id)) {
        field.settings[configField.id] = fieldValue;
      }
    });
  }

  // Handle any remaining fields that aren't core properties or in configFields
  Object.keys(rest).forEach((key) => {
    const value = rest[key];
    const coreProperties = ["id", "name", "type", "title", "description", "value", "context"];
    if (value !== undefined && value !== null && value !== "" && !coreProperties.includes(key)) {
      field.settings[key] = value;
    }
  });

  return field;
};
