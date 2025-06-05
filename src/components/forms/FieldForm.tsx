import React from "react";
import {useForm} from "@tanstack/react-form";
import {Button} from "../ui/Button";
import {SaveIcon, XIcon} from "@/components/icons";

export interface FieldData {
  id?: string;
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

const fieldTypes = [
  {value: "text", label: "Text"},
  {value: "textarea", label: "Textarea"},
  {value: "select", label: "Select"},
  {value: "radio", label: "Radio"},
  {value: "checkbox", label: "Checkbox"},
  {value: "media", label: "Media"},
  {value: "range", label: "Range"},
  {value: "switcher", label: "Switcher"},
  {value: "datetime", label: "DateTime"},
  {value: "wysiwyg", label: "WYSIWYG"},
  {value: "button", label: "Button"},
  {value: "repeater", label: "Repeater"},
  {value: "react-select", label: "React Select"},
];

export const FieldForm: React.FC<FieldFormProps> = ({
  field,
  onSave,
  onCancel,
  error,
  isLoading = false,
}) => {
  // Generate a unique ID for new fields
  const generateFieldId = (label: string): string => {
    const baseId = label
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "_")
      .replace(/^_+|_+$/g, "");
    const timestamp = Date.now().toString(36);
    return `field_${baseId}_${timestamp}`;
  };

  const form = useForm({
    defaultValues: {
      id: field?.id || "",
      type: field?.type || "text",
      label: field?.label || "",
      description: field?.description || "",
      required: field?.required || false,
      default_value: field?.default_value || "",
      options: field?.options || {},
    },
    onSubmit: async ({value}) => {
      // Validate required fields
      if (!value.label.trim()) {
        form.setFieldMeta("label", (prev) => ({
          ...prev,
          errors: ["Label is required"],
        }));
        return;
      }

      // Generate ID for new fields
      const formData: FieldData = {
        ...value,
        // Generate ID if this is a new field, otherwise keep existing ID
        id: value.id || generateFieldId(value.label),
      };

      onSave(formData);
    },
  });

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
        {" "}
        {field?.id ? (
          <form.Field name="id">
            {(field) => (
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Field ID
                </label>
                <input
                  name={field.name}
                  type="text"
                  value={field.state.value}
                  disabled
                  className="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500"
                />
              </div>
            )}
          </form.Field>
        ) : (
          <form.Field name="label">
            {(labelField) => (
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Field ID (Preview)
                </label>
                <div className="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50 text-gray-500 text-sm">
                  {labelField.state.value
                    ? generateFieldId(labelField.state.value)
                    : "field_example_" + Date.now().toString(36).slice(-4)}
                </div>
                <p className="text-xs text-gray-500 mt-1">
                  This ID will be auto-generated based on the label
                </p>
              </div>
            )}
          </form.Field>
        )}
        <form.Field
          name="type"
          validators={{
            onChange: ({value}) =>
              !value ? "Field type is required" : undefined,
          }}
        >
          {(field) => (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Field Type *
              </label>
              <select
                name={field.name}
                value={field.state.value}
                onBlur={field.handleBlur}
                onChange={(e) => field.handleChange(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              >
                {fieldTypes.map((type) => (
                  <option key={type.value} value={type.value}>
                    {type.label}
                  </option>
                ))}
              </select>
              {field.state.meta.errors && (
                <div className="mt-1 text-sm text-red-600">
                  {field.state.meta.errors.join(", ")}
                </div>
              )}
            </div>
          )}
        </form.Field>
        <form.Field
          name="label"
          validators={{
            onChange: ({value}) =>
              !value?.trim() ? "Label is required" : undefined,
          }}
        >
          {(field) => (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Label *
              </label>
              <input
                name={field.name}
                type="text"
                value={field.state.value}
                onBlur={field.handleBlur}
                onChange={(e) => field.handleChange(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
              {field.state.meta.errors && (
                <div className="mt-1 text-sm text-red-600">
                  {field.state.meta.errors.join(", ")}
                </div>
              )}
            </div>
          )}
        </form.Field>
        <form.Field name="description">
          {(field) => (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Description
              </label>
              <textarea
                name={field.name}
                value={field.state.value}
                onBlur={field.handleBlur}
                onChange={(e) => field.handleChange(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                rows={3}
              />
              {field.state.meta.errors && (
                <div className="mt-1 text-sm text-red-600">
                  {field.state.meta.errors.join(", ")}
                </div>
              )}
            </div>
          )}
        </form.Field>
        <form.Field name="required">
          {(field) => (
            <div>
              <label className="flex items-center space-x-2">
                <input
                  name={field.name}
                  type="checkbox"
                  checked={field.state.value}
                  onBlur={field.handleBlur}
                  onChange={(e) => field.handleChange(e.target.checked)}
                  className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span className="text-sm font-medium text-gray-700">
                  Required
                </span>
              </label>
              {field.state.meta.errors && (
                <div className="mt-1 text-sm text-red-600">
                  {field.state.meta.errors.join(", ")}
                </div>
              )}
            </div>
          )}
        </form.Field>
        <form.Field name="default_value">
          {(field) => (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Default Value
              </label>
              <input
                name={field.name}
                type="text"
                value={field.state.value || ""}
                onBlur={field.handleBlur}
                onChange={(e) => field.handleChange(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              {field.state.meta.errors && (
                <div className="mt-1 text-sm text-red-600">
                  {field.state.meta.errors.join(", ")}
                </div>
              )}
            </div>
          )}
        </form.Field>
        <div className="flex justify-end space-x-2 pt-4 border-t border-gray-200">
          <Button
            type="button"
            variant="secondary"
            onClick={onCancel}
            className="flex items-center space-x-2"
            disabled={isLoading}
          >
            <XIcon size={16} />
            <span>Cancel</span>
          </Button>
          <form.Subscribe
            selector={(state) => [state.canSubmit, state.isSubmitting]}
          >
            {([canSubmit, isSubmitting]) => (
              <Button
                type="submit"
                variant="primary"
                className="flex items-center space-x-2"
                disabled={!canSubmit || isSubmitting || isLoading}
              >
                <SaveIcon size={16} />
                <span>
                  {isSubmitting || isLoading ? "Saving..." : "Save Field"}
                </span>
              </Button>
            )}
          </form.Subscribe>
        </div>
      </form>
    </div>
  );
};
