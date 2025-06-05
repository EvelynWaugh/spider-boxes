import React from "react";
import {StarIcon} from "@radix-ui/react-icons";

interface FieldOption {
  label: string;
  value?: string;
}

interface DynamicField {
  id: string;
  type: string;
  title: string;
  description?: string;
  value: any;
  required?: boolean;
  options?: Record<string, FieldOption>;
  min?: number;
  max?: number;
  step?: number;
  rows?: number;
  placeholder?: string;
}

interface DynamicFieldRendererProps {
  field: DynamicField;
  value: any;
  onChange: (fieldId: string, value: any) => void;
}

export const DynamicFieldRenderer: React.FC<DynamicFieldRendererProps> = ({
  field,
  value,
  onChange,
}) => {
  const handleChange = (newValue: any) => {
    onChange(field.id, newValue);
  };

  const renderField = () => {
    switch (field.type) {
      case "text":
        return (
          <input
            type="text"
            value={value || ""}
            onChange={(e) => handleChange(e.target.value)}
            placeholder={field.placeholder}
            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          />
        );

      case "textarea":
        return (
          <textarea
            value={value || ""}
            onChange={(e) => handleChange(e.target.value)}
            rows={field.rows || 4}
            placeholder={field.placeholder}
            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          />
        );

      case "select":
        return (
          <select
            value={value || ""}
            onChange={(e) => handleChange(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          >
            {field.options &&
              Object.entries(field.options).map(([optionValue, option]) => (
                <option key={optionValue} value={optionValue}>
                  {option.label}
                </option>
              ))}
          </select>
        );

      case "range":
        return (
          <div className="space-y-2">
            <input
              type="range"
              min={field.min || 0}
              max={field.max || 100}
              step={field.step || 1}
              value={value || field.min || 0}
              onChange={(e) => handleChange(parseInt(e.target.value))}
              className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
            />
            <div className="flex justify-between items-center">
              <span className="text-sm text-gray-500">{field.min || 0}</span>
              <div className="flex items-center space-x-1">
                {field.id === "review_rating" && (
                  <>
                    {[1, 2, 3, 4, 5].map((star) => (
                      <StarIcon
                        key={star}
                        className={`w-4 h-4 ${
                          star <= (value || 0)
                            ? "text-yellow-400 fill-current"
                            : "text-gray-300"
                        }`}
                      />
                    ))}
                    <span className="ml-2 text-sm font-medium">
                      {value || 0}
                    </span>
                  </>
                )}
                {field.id !== "review_rating" && (
                  <span className="text-sm font-medium">{value || 0}</span>
                )}
              </div>
              <span className="text-sm text-gray-500">{field.max || 100}</span>
            </div>
          </div>
        );

      case "datetime":
        return (
          <input
            type="datetime-local"
            value={value ? new Date(value).toISOString().slice(0, 16) : ""}
            onChange={(e) => handleChange(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          />
        );

      case "checkbox":
        if (field.options) {
          // Multiple checkboxes
          const selectedValues = Array.isArray(value) ? value : [];
          return (
            <div className="space-y-2">
              {Object.entries(field.options).map(([optionValue, option]) => (
                <label key={optionValue} className="flex items-center">
                  <input
                    type="checkbox"
                    checked={selectedValues.includes(optionValue)}
                    onChange={(e) => {
                      if (e.target.checked) {
                        handleChange([...selectedValues, optionValue]);
                      } else {
                        handleChange(
                          selectedValues.filter((v) => v !== optionValue)
                        );
                      }
                    }}
                    className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                  />
                  <span className="ml-2">{option.label}</span>
                </label>
              ))}
            </div>
          );
        } else {
          // Single checkbox
          return (
            <label className="flex items-center">
              <input
                type="checkbox"
                checked={!!value}
                onChange={(e) => handleChange(e.target.checked)}
                className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
              />
              <span className="ml-2">{field.title}</span>
            </label>
          );
        }

      case "radio":
        return (
          <div className="space-y-2">
            {field.options &&
              Object.entries(field.options).map(([optionValue, option]) => (
                <label key={optionValue} className="flex items-center">
                  <input
                    type="radio"
                    name={field.id}
                    value={optionValue}
                    checked={value === optionValue}
                    onChange={(e) => handleChange(e.target.value)}
                    className="border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                  />
                  <span className="ml-2">{option.label}</span>
                </label>
              ))}
          </div>
        );

      case "switcher":
        return (
          <button
            type="button"
            onClick={() => handleChange(!value)}
            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
              value ? "bg-primary-600" : "bg-gray-200"
            }`}
          >
            <span
              className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                value ? "translate-x-6" : "translate-x-1"
              }`}
            />
          </button>
        );

      default:
        return (
          <div className="text-gray-500 italic">
            Unsupported field type: {field.type}
          </div>
        );
    }
  };
  return (
    <div className="dynamic-field">
      <label className="block text-sm font-medium text-gray-700">
        {field.title}
        {field.required && <span className="required ml-1">*</span>}
      </label>
      {renderField()}
      {field.description && <p className="description">{field.description}</p>}
    </div>
  );
};
