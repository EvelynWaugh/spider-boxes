import React, { useCallback, useState, useEffect } from "react";

import { FormApi } from "@tanstack/react-form";

import { useQuery } from "@tanstack/react-query";

import { useAPI } from "@/hooks/useAPI";

import { StarIcon } from "@radix-ui/react-icons";

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/Select";
import TagsField from "@/components/ui/TagsField";
import MediaField from "@/components/ui/MediaField";

import * as SelectRadix from "@radix-ui/react-select";

import { PlusIcon, ChevronDownIcon, CheckIcon } from "@radix-ui/react-icons";

import { useFieldValidation } from "@/hooks/useFieldValidation";

interface Product {
  id: number;
  name: string;
  slug: string;
  type: string;
  price: string;
  image: string[] | null;
}

interface FieldOption {
  label: string;
  value?: string;
}

export interface DynamicField {
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
  multiple?: boolean;
  media_type?: string;
  validation?: Record<string, any> | null;
  meta_field?: boolean;
}

interface DynamicFieldRendererProps {
  field: DynamicField;
  asyncOptions?: boolean;
  // value: any;
  onChange?: (value: any) => void; // Changed to a simpler callback
  // onChange: (fieldId: string, isMeta: boolean | undefined, value: any) => void;
  formApi: FormApi<any, any>;
  validationRules?: {
    required?: boolean;
    minLength?: number;
    maxLength?: number;
    min?: number;
    max?: number;
    pattern?: RegExp;
    custom?: (value: any) => string | null;
  };
}

export const DynamicFieldRenderer: React.FC<DynamicFieldRendererProps> = ({
  field,
  //   value,
  onChange,
  asyncOptions,
  formApi,
  validationRules,
}) => {
  const { get } = useAPI();
  const validateField = useFieldValidation(field, validationRules);
  // Validation function
  //   const validateField = useCallback(
  //     (fieldValue: any) => {
  //       const errors: string[] = [];
  //       console.log(fieldValue, validationRules);
  //       const actualValue = fieldValue.value;
  //       // Built-in required validation
  //       if (
  //         validationRules?.required &&
  //         (!actualValue ||
  //           actualValue === "" ||
  //           (Array.isArray(actualValue) && actualValue.length === 0))
  //       ) {
  //         console.log("PUSH ERROR");
  //         errors.push(`${field.title} is required`);
  //       }

  //       // Custom validation rules
  //       if (validationRules) {
  //         if (
  //           validationRules.minLength &&
  //           typeof actualValue === "string" &&
  //           actualValue.length < validationRules.minLength
  //         ) {
  //           errors.push(
  //             `${field.title} must be at least ${validationRules.minLength} characters`
  //           );
  //         }

  //         if (
  //           validationRules.maxLength &&
  //           typeof actualValue === "string" &&
  //           actualValue.length > validationRules.maxLength
  //         ) {
  //           errors.push(
  //             `${field.title} must not exceed ${validationRules.maxLength} characters`
  //           );
  //         }

  //         if (
  //           validationRules.min !== undefined &&
  //           typeof actualValue === "number" &&
  //           actualValue < validationRules.min
  //         ) {
  //           errors.push(`${field.title} must be at least ${validationRules.min}`);
  //         }

  //         if (
  //           validationRules.max !== undefined &&
  //           typeof actualValue === "number" &&
  //           actualValue > validationRules.max
  //         ) {
  //           errors.push(`${field.title} must not exceed ${validationRules.max}`);
  //         }

  //         if (validationRules.pattern) {
  //           if (typeof validationRules.pattern === "string") {
  //             const patternString = validationRules.pattern.slice(1, -1);

  //             // 2. Create a new RegExp object from the cleaned string.
  //             validationRules.pattern = new RegExp(patternString);
  //           }
  //         }
  //         if (
  //           validationRules.pattern &&
  //           actualValue !== "" &&
  //           !validationRules.pattern.test(actualValue)
  //         ) {
  //           errors.push(`${field.title} format is invalid`);
  //         }

  //         if (validationRules.custom) {
  //           const customError = validationRules.custom(actualValue);
  //           if (customError) {
  //             errors.push(customError);
  //           }
  //         }
  //       }
  //       console.log("errors", errors);
  //       return errors.length > 0 ? errors[0] : undefined;
  //     },
  //     [field, validationRules]
  //   );

  //   console.log(field, value);

  // Fetch products for selection
  const { data: productsData, isLoading: isLoadingProducts } = useQuery({
    queryKey: ["products"],
    queryFn: () => get("/products?per_page=50"),
    enabled: asyncOptions && field.type === "product_select" && !field.value, // Only fetch if no specific product is set
  });

  const products: Product[] = productsData?.products || [];

  const renderField = () => {
    // The component now relies solely on TanStack Form
    return (
      <formApi.Field
        name={field.id}
        // validatorAdapter={myValidator()}
        // The validators will run on change and blur, powered by TanStack Form
        validators={{
          onChange: validateField,
          onMount: validateField, // Validate on mount to ensure initial value is valid
          // onBlur: validateField,
        }}
      >
        {(fieldApi) => {
          // fieldApi provides everything: value, errors, and the change handler.
          // This is the clean, single-source-of-truth approach.
          return renderFieldInput(
            fieldApi.state.value,
            fieldApi.handleChange, // Use the built-in handleChange
            fieldApi.handleBlur,
            fieldApi.state.meta.errors,
            fieldApi.state.meta.isTouched,
          );
        }}
      </formApi.Field>
    );
  };

  const renderFieldInput = (
    currentValue: any,
    handleChange: (value: any) => void,
    handleBlur?: () => void,
    errors?: string[],
    isTouched?: boolean,
  ) => {
    const showErrors = isTouched && errors && errors.length > 0;
    switch (field.type) {
      case "text":
        return (
          <div>
            <input
              type="text"
              value={currentValue || ""}
              onChange={(e) => handleChange(e.target.value)}
              onBlur={handleBlur}
              placeholder={field.placeholder}
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
            />
            {showErrors && <div className="mt-1 text-sm text-red-600">{errors.join(", ")}</div>}
          </div>
        );

      case "number":
        return (
          <div>
            <input
              type="number"
              value={currentValue || 0}
              onChange={(e) => handleChange(e.target.value)}
              onBlur={handleBlur}
              placeholder={field.placeholder}
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
            />
            {showErrors && <div className="mt-1 text-sm text-red-600">{errors.join(", ")}</div>}
          </div>
        );

      case "textarea":
        return (
          <div>
            <textarea
              value={currentValue || ""}
              onChange={(e) => handleChange(e.target.value)}
              onBlur={handleBlur}
              rows={field.rows || 4}
              placeholder={field.placeholder}
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
            />
            {showErrors && <div className="mt-1 text-sm text-red-600">{errors.join(", ")}</div>}
          </div>
        );

      case "select":
        return (
          <div>
            <Select
              value={currentValue || ""}
              onValueChange={(value) => {
                handleChange(value);
                if (onChange) {
                  onChange(value);
                }
              }}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder={field.placeholder || "Choose a status..."} />
              </SelectTrigger>
              <SelectContent>
                {field.options &&
                  Object.entries(field.options).map(([optionValue, option]) => (
                    <SelectItem key={optionValue} value={optionValue}>
                      {option.label}
                    </SelectItem>
                  ))}
              </SelectContent>
            </Select>
            {showErrors && <div className="mt-1 text-sm text-red-600">{errors.join(", ")}</div>}
          </div>
        );

      case "range":
        return (
          <div>
            <div className="space-y-2">
              <input
                type="range"
                min={field.min || 0}
                max={field.max || 100}
                step={field.step || 1}
                value={currentValue || field.min || 0}
                onChange={(e) => handleChange(parseInt(e.target.value))}
                onBlur={handleBlur}
                className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
              />
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-500">{field.min || 0}</span>
                <div className="flex items-center space-x-1">
                  {field.id === "rating" && (
                    <>
                      {[1, 2, 3, 4, 5].map((star) => (
                        <StarIcon
                          key={star}
                          className={`w-4 h-4 ${star <= (currentValue || 0) ? "text-yellow-400 fill-current" : "text-gray-300"}`}
                        />
                      ))}
                      <span className="ml-2 text-sm font-medium">{currentValue || field.min || 0}</span>
                    </>
                  )}
                  {field.id !== "rating" && <span className="text-sm font-medium">{currentValue || field.min || 0}</span>}
                </div>
                <span className="text-sm text-gray-500">{field.max || 100}</span>
              </div>
            </div>
            {showErrors && <div className="mt-1 text-sm text-red-600">{errors.join(", ")}</div>}
          </div>
        );
      case "datetime":
        return (
          <input
            type="datetime-local"
            value={currentValue ? new Date(currentValue).toISOString().slice(0, 16) : ""}
            onChange={(e) => handleChange(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          />
        );

      case "checkbox":
        if (field.options) {
          // Multiple checkboxes
          const selectedValues = Array.isArray(currentValue) ? currentValue : [];
          return (
            <div>
              <div className="space-y-2">
                {Object.entries(field.options).map(([optionValue, option]) => (
                  <label key={optionValue} className="flex items-center">
                    <input
                      type="checkbox"
                      checked={selectedValues.includes(optionValue)}
                      onChange={(e) => {
                        const newValues = e.target.checked
                          ? [...selectedValues, optionValue]
                          : selectedValues.filter((v) => v !== optionValue);
                        handleChange(newValues);
                      }}
                      onBlur={handleBlur}
                      className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                    />
                    <span className="ml-2">{option.label}</span>
                  </label>
                ))}
              </div>
              {showErrors && <div className="mt-1 text-sm text-red-600">{errors.join(", ")}</div>}
            </div>
          );
        } else {
          // Single checkbox
          return (
            <div>
              <label className="flex items-center">
                <input
                  type="checkbox"
                  checked={!!currentValue}
                  onChange={(e) => handleChange(e.target.checked)}
                  onBlur={handleBlur}
                  className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                />
                <span className="ml-2">{field.title}</span>
              </label>
              {showErrors && <div className="mt-1 text-sm text-red-600">{errors.join(", ")}</div>}
            </div>
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
                    checked={currentValue === optionValue}
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
            onClick={() => handleChange(!currentValue)}
            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
              currentValue ? "bg-primary-600" : "bg-gray-200"
            }`}
          >
            <span
              className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                currentValue ? "translate-x-6" : "translate-x-1"
              }`}
            />
          </button>
        );

      case "media":
        return (
          <div>
            <MediaField value={currentValue} onChange={handleChange} multiple={field.multiple} mediaType={field.media_type} />
            {showErrors && <div className="mt-1 text-sm text-red-600">{errors.join(", ")}</div>}
          </div>
        );

      case "tags":
        return (
          <div>
            <TagsField value={currentValue} onChange={handleChange} placeholder={field.placeholder || "Enter tags..."} />
            {showErrors && <div className="mt-1 text-sm text-red-600">{errors.join(", ")}</div>}
          </div>
        );

      case "product_select":
        return (
          <div className="space-y-2">
            <SelectRadix.Root value={currentValue || ""} onValueChange={(value) => handleChange(value)}>
              <SelectRadix.Trigger className="w-full flex items-center justify-between px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <SelectRadix.Value placeholder={field.placeholder || "Select"} />
                <SelectRadix.Icon>
                  <ChevronDownIcon />
                </SelectRadix.Icon>
              </SelectRadix.Trigger>
              <SelectRadix.Portal>
                <SelectRadix.Content className="bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto z-50">
                  <SelectRadix.Viewport>
                    {isLoadingProducts ? (
                      <div className="p-4 text-center text-gray-500">Loading products...</div>
                    ) : (
                      products.map((product) => (
                        <SelectRadix.Item
                          key={product.id}
                          value={product.id.toString()}
                          className="flex items-center space-x-3 px-3 py-2 hover:bg-gray-50 cursor-pointer data-[highlighted]:bg-blue-50 data-[highlighted]:outline-none"
                        >
                          <SelectRadix.ItemText className="flex items-center space-x-3">
                            {product.image && <img src={product.image[0]} alt={product.name} className="w-8 h-8 object-cover rounded" />}
                            <div>
                              <div className="font-medium">{product.name}</div>
                              <div className="text-sm text-gray-500">${product.price}</div>
                            </div>
                          </SelectRadix.ItemText>
                          <SelectRadix.ItemIndicator>
                            <CheckIcon />
                          </SelectRadix.ItemIndicator>
                        </SelectRadix.Item>
                      ))
                    )}
                  </SelectRadix.Viewport>
                </SelectRadix.Content>
              </SelectRadix.Portal>
            </SelectRadix.Root>
            {showErrors && <div className="mt-1 text-sm text-red-600">{errors.join(", ")}</div>}
          </div>
        );

      default:
        return <div className="text-gray-500 italic">Unsupported field type: {field.type}</div>;
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
