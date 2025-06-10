import { useCallback } from "react";
import { DynamicField } from "@/components/DynamicFieldRenderer";

interface ValidationRules {
  required?: boolean;
  minLength?: number;
  maxLength?: number;
  min?: number;
  max?: number;
  pattern?: RegExp | string;
  custom?: (value: any) => string | null;
}

export const useFieldValidation = (field: DynamicField, validationRules?: ValidationRules) => {
  const validateField = useCallback(
    (fieldValue: any) => {
      const errors: string[] = [];
      const actualValue = fieldValue?.value ?? "";

      // Built-in required validation
      if (validationRules?.required && (!actualValue || actualValue === "" || (Array.isArray(actualValue) && actualValue.length === 0))) {
        errors.push(`${field.title} is required`);
      }

      // Custom validation rules
      if (validationRules) {
        if (validationRules.minLength && typeof actualValue === "string" && actualValue.length < validationRules.minLength) {
          errors.push(`${field.title} must be at least ${validationRules.minLength} characters`);
        }

        if (validationRules.maxLength && typeof actualValue === "string" && actualValue.length > validationRules.maxLength) {
          errors.push(`${field.title} must not exceed ${validationRules.maxLength} characters`);
        }

        if (validationRules.min !== undefined && typeof actualValue === "number" && actualValue < validationRules.min) {
          errors.push(`${field.title} must be at least ${validationRules.min}`);
        }

        if (validationRules.max !== undefined && typeof actualValue === "number" && actualValue > validationRules.max) {
          errors.push(`${field.title} must not exceed ${validationRules.max}`);
        }

        // Handle pattern validation
        if (validationRules.pattern) {
          let pattern = validationRules.pattern;

          if (typeof pattern === "string") {
            const patternString = pattern.slice(1, -1);
            pattern = new RegExp(patternString);
          }

          if (actualValue !== "" && !pattern.test(actualValue)) {
            errors.push(`${field.title} format is invalid`);
          }
        }

        if (validationRules.custom) {
          const customError = validationRules.custom(actualValue);
          if (customError) {
            errors.push(customError);
          }
        }
      }

      // console.log("Validation errors for field:", field.id, errors, actualValue);

      return errors.length > 0 ? errors[0] : undefined;
    },
    [field, validationRules],
  );

  return validateField;
};
