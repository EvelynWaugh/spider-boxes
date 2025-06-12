import React from "react";
import { motion } from "framer-motion";
import { cn } from "../utils/cn";

interface FieldConfig {
  id: string;
  type: string;
  label?: string;
  description?: string;
  placeholder?: string;
  required?: boolean;
  validation?: {
    required?: boolean;
    minLength?: number;
    maxLength?: number;
    pattern?: string;
    min?: number;
    max?: number;
  };
  options?: Array<{ label: string; value: string }>;
  multiple?: boolean;
  class_name?: string;
  [key: string]: any;
}

interface FieldRendererProps {
  config: FieldConfig;
  value?: any;
  onChange?: (value: any) => void;
  onBlur?: () => void;
  error?: string;
  disabled?: boolean;
}

export const FieldRenderer: React.FC<FieldRendererProps> = ({ config, value, onChange, onBlur, error, disabled = false }) => {
  const handleChange = (newValue: any) => {
    if (onChange) {
      onChange(newValue);
    }
  };

  const renderField = () => {
    const commonProps = {
      id: config.id,
      name: config.id,
      disabled,
      onBlur,
      className: cn("field-input", config.className, error && "field-error"),
    };

    switch (config.type) {
      case "text":
        return (
          <input
            type="text"
            value={value || ""}
            onChange={(e) => handleChange(e.target.value)}
            placeholder={config.placeholder}
            required={config.required}
            minLength={config.validation?.minLength}
            maxLength={config.validation?.maxLength}
            pattern={config.validation?.pattern}
            {...commonProps}
          />
        );

      case "textarea":
        return (
          <textarea
            value={value || ""}
            onChange={(e) => handleChange(e.target.value)}
            placeholder={config.placeholder}
            required={config.required}
            minLength={config.validation?.minLength}
            maxLength={config.validation?.maxLength}
            rows={config.rows || 4}
            {...commonProps}
          />
        );

      case "select":
        return (
          <select
            value={value || ""}
            onChange={(e) => handleChange(e.target.value)}
            required={config.required}
            multiple={config.multiple}
            {...commonProps}
          >
            {!config.required && !config.multiple && <option value="">Select an option</option>}
            {config.options?.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        );

      case "checkbox":
        return (
          <div className="checkbox-group">
            {config.options ? (
              config.options.map((option) => (
                <label key={option.value} className="checkbox-item">
                  <input
                    type="checkbox"
                    value={option.value}
                    checked={Array.isArray(value) ? value.includes(option.value) : false}
                    onChange={(e) => {
                      const currentValue = Array.isArray(value) ? value : [];
                      if (e.target.checked) {
                        handleChange([...currentValue, option.value]);
                      } else {
                        handleChange(currentValue.filter((v) => v !== option.value));
                      }
                    }}
                    {...commonProps}
                  />
                  <span className="checkbox-label">{option.label}</span>
                </label>
              ))
            ) : (
              <label className="checkbox-item">
                <input type="checkbox" checked={Boolean(value)} onChange={(e) => handleChange(e.target.checked)} {...commonProps} />
                <span className="checkbox-label">{config.label}</span>
              </label>
            )}
          </div>
        );

      case "radio":
        return (
          <div className="radio-group">
            {config.options?.map((option) => (
              <label key={option.value} className="radio-item">
                {" "}
                <input
                  type="radio"
                  value={option.value}
                  checked={value === option.value}
                  onChange={(e) => handleChange(e.target.value)}
                  required={config.required}
                  {...commonProps}
                />
                <span className="radio-label">{option.label}</span>
              </label>
            ))}
          </div>
        );

      case "range":
        return (
          <div className="range-wrapper">
            <input
              type="range"
              value={value || config.min || 0}
              onChange={(e) => handleChange(Number(e.target.value))}
              min={config.min || 0}
              max={config.max || 100}
              step={config.step || 1}
              {...commonProps}
              className={cn("field-range", config.className)}
            />
            <div className="range-value">{value || config.min || 0}</div>
          </div>
        );

      case "date":
        return (
          <input
            type="date"
            value={value || ""}
            onChange={(e) => handleChange(e.target.value)}
            required={config.required}
            min={config.min}
            max={config.max}
            {...commonProps}
          />
        );

      case "datetime":
        return (
          <input
            type="datetime-local"
            value={value || ""}
            onChange={(e) => handleChange(e.target.value)}
            required={config.required}
            min={config.min}
            max={config.max}
            {...commonProps}
          />
        );

      case "number":
        return (
          <input
            type="number"
            value={value || ""}
            onChange={(e) => handleChange(Number(e.target.value))}
            placeholder={config.placeholder}
            required={config.required}
            min={config.validation?.min}
            max={config.validation?.max}
            step={config.step || 1}
            {...commonProps}
          />
        );

      case "email":
        return (
          <input
            type="email"
            value={value || ""}
            onChange={(e) => handleChange(e.target.value)}
            placeholder={config.placeholder}
            required={config.required}
            {...commonProps}
          />
        );

      case "url":
        return (
          <input
            type="url"
            value={value || ""}
            onChange={(e) => handleChange(e.target.value)}
            placeholder={config.placeholder}
            required={config.required}
            {...commonProps}
          />
        );

      case "password":
        return (
          <input
            type="password"
            value={value || ""}
            onChange={(e) => handleChange(e.target.value)}
            placeholder={config.placeholder}
            required={config.required}
            minLength={config.validation?.minLength}
            maxLength={config.validation?.maxLength}
            {...commonProps}
          />
        );

      case "file":
        return (
          <input
            type="file"
            onChange={(e) => {
              const files = e.target.files;
              if (config.multiple) {
                handleChange(files ? Array.from(files) : []);
              } else {
                handleChange(files?.[0] || null);
              }
            }}
            accept={config.accept}
            multiple={config.multiple}
            {...commonProps}
          />
        );

      case "hidden":
        return <input type="hidden" value={value || ""} {...commonProps} />;

      case "wysiwyg":
        return (
          <div className="wysiwyg-wrapper">
            <textarea
              value={value || ""}
              onChange={(e) => handleChange(e.target.value)}
              placeholder={config.placeholder}
              required={config.required}
              rows={config.rows || 6}
              {...commonProps}
              className={cn("field-wysiwyg", config.className)}
            />
            <div className="wysiwyg-note">Rich text editor would be rendered here in full implementation</div>
          </div>
        );

      case "media":
        return (
          <div className="media-field-wrapper">
            <div className="media-preview">
              {value && (
                <div className="media-item">
                  {value.type?.startsWith("image/") ? (
                    <img src={value.url} alt={value.name || "Selected media"} />
                  ) : (
                    <div className="media-file">
                      <span className="media-icon">📄</span>
                      <span className="media-name">{value.name}</span>
                    </div>
                  )}
                  <button type="button" onClick={() => handleChange(null)} className="media-remove">
                    Remove
                  </button>
                </div>
              )}
            </div>
            <input
              type="file"
              onChange={(e) => {
                const file = e.target.files?.[0];
                if (file) {
                  const reader = new FileReader();
                  reader.onload = (event) => {
                    handleChange({
                      file,
                      url: event.target?.result,
                      name: file.name,
                      type: file.type,
                      size: file.size,
                    });
                  };
                  reader.readAsDataURL(file);
                }
              }}
              accept={config.accept || "image/*"}
              {...commonProps}
            />
          </div>
        );

      case "switcher":
        return (
          <label className="switcher-wrapper">
            <input
              type="checkbox"
              checked={Boolean(value)}
              onChange={(e) => handleChange(e.target.checked)}
              {...commonProps}
              className="switcher-input"
            />
            <span className="switcher-slider"></span>
            {config.label && <span className="switcher-label">{config.label}</span>}
          </label>
        );

      default:
        return <div className="field-unsupported">Unsupported field type: {config.type}</div>;
    }
  };

  return (
    <motion.div
      className={cn("field-wrapper", `field-type-${config.type}`, error && "field-has-error")}
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -10 }}
      transition={{ duration: 0.2 }}
    >
      {config.label && config.type !== "checkbox" && config.type !== "switcher" && (
        <label htmlFor={config.id} className="field-label">
          {config.label}
          {config.required && <span className="field-required">*</span>}
        </label>
      )}

      <div className="field-control">{renderField()}</div>

      {config.description && <div className="field-description">{config.description}</div>}

      {error && (
        <motion.div
          className="field-error-message"
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          exit={{ opacity: 0, scale: 0.9 }}
          transition={{ duration: 0.2 }}
        >
          {error}
        </motion.div>
      )}
    </motion.div>
  );
};
