import React, {Fragment} from "react";
import {
  SpiderBoxesHooks,
  SPIDER_BOXES_HOOKS,
  useComponentLifecycle,
  useFilteredContent,
} from "../hooks/useSpiderBoxesHooks";

interface FieldComponentProps {
  field: {
    id: string;
    type: string;
    title: string;
    description?: string;
    value?: any;
    options?: Record<string, any>;
  };
  onChange?: (value: any) => void;
}

/**
 * Example field component using WordPress hooks
 */
const FieldComponent: React.FC<FieldComponentProps> = ({field, onChange}) => {
  // Use component lifecycle hooks
  useComponentLifecycle(`field-${field.id}`);

  // Apply filters to field title and description
  const filteredTitle = useFilteredContent(
    SPIDER_BOXES_HOOKS.FILTER_CONTENT,
    field.title
  );
  const filteredDescription = useFilteredContent(
    SPIDER_BOXES_HOOKS.FILTER_CONTENT,
    field.description || ""
  );

  const handleValueChange = (newValue: any) => {
    // Trigger action before value change
    SpiderBoxesHooks.doAction(SPIDER_BOXES_HOOKS.FIELD_VALUE_CHANGED, {
      fieldId: field.id,
      oldValue: field.value,
      newValue,
    });

    // Apply filters to the new value
    const filteredValue = SpiderBoxesHooks.applyFilters(
      `spider_boxes_field_value_${field.type}`,
      newValue,
      field
    );

    onChange?.(filteredValue);
  };

  const renderField = () => {
    switch (field.type) {
      case "text":
        return (
          <input
            type="text"
            value={field.value || ""}
            onChange={(e) => handleValueChange(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        );

      case "textarea":
        return (
          <textarea
            value={field.value || ""}
            onChange={(e) => handleValueChange(e.target.value)}
            rows={4}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        );

      case "select":
        return (
          <select
            value={field.value || ""}
            onChange={(e) => handleValueChange(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select an option</option>
            {field.options &&
              Object.entries(field.options).map(([key, option]) => (
                <option key={key} value={key}>
                  {typeof option === "object" ? option.label : option}
                </option>
              ))}
          </select>
        );

      case "checkbox":
        return (
          <input
            type="checkbox"
            checked={field.value || false}
            onChange={(e) => handleValueChange(e.target.checked)}
            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          />
        );

      default:
        return <div>Unsupported field type: {field.type}</div>;
    }
  };

  return (
    <Fragment>
      {SpiderBoxesHooks.applyFilters(
        SPIDER_BOXES_HOOKS.BEFORE_FIELD_RENDER,
        null,
        field
      )}

      <div className="field-component mb-4">
        <label className="block text-sm font-medium text-gray-700 mb-2">
          {filteredTitle}
        </label>

        {renderField()}

        {filteredDescription && (
          <p className="mt-1 text-sm text-gray-500">{filteredDescription}</p>
        )}
      </div>

      {SpiderBoxesHooks.applyFilters(
        SPIDER_BOXES_HOOKS.AFTER_FIELD_RENDER,
        null,
        field
      )}
    </Fragment>
  );
};

export default FieldComponent;
