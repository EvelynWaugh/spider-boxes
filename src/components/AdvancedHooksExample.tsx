import React, {useEffect, useState} from "react";
import {
  SpiderBoxesHooks,
  SPIDER_BOXES_HOOKS,
  useComponentLifecycle,
  useFilteredContent,
} from "../hooks/useSpiderBoxesHooks";
import FieldComponent from "./FieldComponent";

/**
 * Advanced example showing hooks integration with Spider Boxes components
 */
const AdvancedHooksExample: React.FC = () => {
  const [formData, setFormData] = useState<Record<string, any>>({});
  const [validationErrors, setValidationErrors] = useState<
    Record<string, string>
  >({});

  // Use component lifecycle hooks
  useComponentLifecycle("AdvancedHooksExample");

  // Apply filters to the page title
  const pageTitle = useFilteredContent(
    SPIDER_BOXES_HOOKS.FILTER_CONTENT,
    "Advanced Hooks Demo"
  );

  useEffect(() => {
    // Add custom filters for form validation
    SpiderBoxesHooks.addFilter(
      SPIDER_BOXES_HOOKS.FORM_VALIDATION,
      "advanced-example/validate-email",
      (errors: Record<string, string>, data: Record<string, any>) => {
        if (data.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
          errors.email = "Please enter a valid email address";
        }
        return errors;
      }
    );

    // Add custom filter for field values
    SpiderBoxesHooks.addFilter(
      "spider_boxes_field_value_text",
      "advanced-example/trim-text",
      (value: string) => {
        return typeof value === "string" ? value.trim() : value;
      }
    );

    // Add action for form submission
    SpiderBoxesHooks.addAction(
      SPIDER_BOXES_HOOKS.BEFORE_FORM_SUBMIT,
      "advanced-example/log-submit",
      (data: Record<string, any>) => {
        console.log("Form data before submit:", data);
      }
    );

    // Add action for field value changes
    SpiderBoxesHooks.addAction(
      SPIDER_BOXES_HOOKS.FIELD_VALUE_CHANGED,
      "advanced-example/track-changes",
      ({fieldId, newValue}: {fieldId: string; newValue: any}) => {
        console.log(`Field ${fieldId} changed to:`, newValue);
      }
    );

    // Add custom content filters
    SpiderBoxesHooks.addFilter(
      SPIDER_BOXES_HOOKS.BEFORE_CONTENT,
      "advanced-example/add-notice",
      () => (
        <div className="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
          <p className="font-bold">Info</p>
          <p>This form uses WordPress-style hooks for extensibility.</p>
        </div>
      )
    );

    return () => {
      // Cleanup hooks on unmount
      SpiderBoxesHooks.removeFilter(
        SPIDER_BOXES_HOOKS.FORM_VALIDATION,
        "advanced-example/validate-email"
      );
      SpiderBoxesHooks.removeFilter(
        "spider_boxes_field_value_text",
        "advanced-example/trim-text"
      );
      SpiderBoxesHooks.removeAction(
        SPIDER_BOXES_HOOKS.BEFORE_FORM_SUBMIT,
        "advanced-example/log-submit"
      );
      SpiderBoxesHooks.removeAction(
        SPIDER_BOXES_HOOKS.FIELD_VALUE_CHANGED,
        "advanced-example/track-changes"
      );
      SpiderBoxesHooks.removeFilter(
        SPIDER_BOXES_HOOKS.BEFORE_CONTENT,
        "advanced-example/add-notice"
      );
    };
  }, []);

  const handleFieldChange = (fieldId: string, value: any) => {
    setFormData((prev) => ({
      ...prev,
      [fieldId]: value,
    }));

    // Clear validation error when field changes
    if (validationErrors[fieldId]) {
      setValidationErrors((prev) => {
        const newErrors = {...prev};
        delete newErrors[fieldId];
        return newErrors;
      });
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // Trigger before submit action
    SpiderBoxesHooks.doAction(SPIDER_BOXES_HOOKS.BEFORE_FORM_SUBMIT, formData);

    // Apply validation filters
    const errors = SpiderBoxesHooks.applyFilters(
      SPIDER_BOXES_HOOKS.FORM_VALIDATION,
      {},
      formData
    );

    if (Object.keys(errors).length > 0) {
      setValidationErrors(errors);
      return;
    }

    // Simulate form submission
    console.log("Form submitted successfully:", formData);

    // Trigger after submit action
    SpiderBoxesHooks.doAction(SPIDER_BOXES_HOOKS.AFTER_FORM_SUBMIT, formData);

    // Reset form
    setFormData({});
    setValidationErrors({});
  };

  // Sample fields configuration
  const fields = [
    {
      id: "name",
      type: "text",
      title: "Full Name",
      description: "Enter your full name",
      value: formData.name || "",
    },
    {
      id: "email",
      type: "text",
      title: "Email Address",
      description: "Enter a valid email address",
      value: formData.email || "",
    },
    {
      id: "message",
      type: "textarea",
      title: "Message",
      description: "Enter your message",
      value: formData.message || "",
    },
    {
      id: "newsletter",
      type: "checkbox",
      title: "Subscribe to Newsletter",
      description: "Receive updates about our products",
      value: formData.newsletter || false,
    },
  ];

  return (
    <div className="advanced-hooks-example max-w-2xl mx-auto p-6">
      <h2 className="text-2xl font-bold mb-6">{pageTitle}</h2>

      {/* Apply before content filter */}
      {SpiderBoxesHooks.applyFilters(SPIDER_BOXES_HOOKS.BEFORE_CONTENT, null)}

      <form onSubmit={handleSubmit} className="space-y-4">
        {fields.map((field) => (
          <div key={field.id}>
            <FieldComponent
              field={field}
              onChange={(value) => handleFieldChange(field.id, value)}
            />
            {validationErrors[field.id] && (
              <p className="text-red-500 text-sm mt-1">
                {validationErrors[field.id]}
              </p>
            )}
          </div>
        ))}

        <div className="pt-4">
          <button
            type="submit"
            className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
          >
            Submit Form
          </button>
        </div>
      </form>

      {/* Apply after content filter */}
      {SpiderBoxesHooks.applyFilters(SPIDER_BOXES_HOOKS.AFTER_CONTENT, null)}

      {/* Debug info */}
      <div className="mt-8 p-4 bg-gray-100 rounded">
        <h3 className="font-bold mb-2">Debug Info (Form Data):</h3>
        <pre className="text-sm overflow-x-auto">
          {JSON.stringify(formData, null, 2)}
        </pre>
      </div>
    </div>
  );
};

export default AdvancedHooksExample;
