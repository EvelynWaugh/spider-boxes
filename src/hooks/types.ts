/**
 * Type definitions for Spider Boxes hooks system
 */

// Global window interface extension
declare global {
  interface Window {
    spiderBoxesHooks?: import("@wordpress/hooks").WPHooks;
  }
}

// Common hook callback types
export type FilterCallback<T = any> = (value: T, ...args: any[]) => T;
export type ActionCallback = (...args: any[]) => void;

// Specific hook types for better type safety
export interface SpiderBoxesHookTypes {
  // Content filters
  spider_boxes_filter_content: FilterCallback<string>;
  spider_boxes_before_content: FilterCallback<React.ReactNode>;
  spider_boxes_after_content: FilterCallback<React.ReactNode>;

  // Field-related hooks
  spider_boxes_before_field_render: FilterCallback<React.ReactNode>;
  spider_boxes_after_field_render: FilterCallback<React.ReactNode>;
  spider_boxes_field_value_changed: ActionCallback;
  spider_boxes_field_value_text: FilterCallback<string>;
  spider_boxes_field_value_textarea: FilterCallback<string>;
  spider_boxes_field_value_select: FilterCallback<string>;
  spider_boxes_field_value_checkbox: FilterCallback<boolean>;

  // Section-related hooks
  spider_boxes_before_section_render: FilterCallback<React.ReactNode>;
  spider_boxes_after_section_render: FilterCallback<React.ReactNode>;

  // Form-related hooks
  spider_boxes_before_form_submit: ActionCallback;
  spider_boxes_after_form_submit: ActionCallback;
  spider_boxes_form_validation: FilterCallback<Record<string, string>>;

  // Component lifecycle hooks
  spider_boxes_component_mount: ActionCallback;
  spider_boxes_component_unmount: ActionCallback;

  // Admin-related hooks
  spider_boxes_admin_page_loaded: ActionCallback;
  spider_boxes_admin_settings_saved: ActionCallback;

  // Review-related hooks (WooCommerce integration)
  spider_boxes_before_review_render: FilterCallback<React.ReactNode>;
  spider_boxes_after_review_render: FilterCallback<React.ReactNode>;
  spider_boxes_review_status_changed: ActionCallback;
}

// Hook names type for autocomplete
export type SpiderBoxesHookName = keyof SpiderBoxesHookTypes;

// Field types
export interface SpiderBoxesField {
  id: string;
  type:
    | "text"
    | "textarea"
    | "select"
    | "checkbox"
    | "radio"
    | "range"
    | "media"
    | "wysiwyg"
    | "datetime"
    | "repeater";
  title: string;
  description?: string;
  value?: any;
  options?: Record<string, any>;
  attributes?: Record<string, any>;
  validation?: {
    required?: boolean;
    min?: number;
    max?: number;
    pattern?: string;
    custom?: (value: any) => string | null;
  };
}

// Section types
export interface SpiderBoxesSection {
  id: string;
  type: string;
  title: string;
  fields: SpiderBoxesField[];
  settings?: Record<string, any>;
}

// Form data types
export interface SpiderBoxesFormData {
  [fieldId: string]: any;
}

// Validation errors type
export interface SpiderBoxesValidationErrors {
  [fieldId: string]: string;
}

// Event types for actions
export interface FieldValueChangedEvent {
  fieldId: string;
  oldValue: any;
  newValue: any;
  field?: SpiderBoxesField;
}

export interface ComponentLifecycleEvent {
  componentName: string;
  timestamp: number;
}

export interface FormSubmitEvent {
  formData: SpiderBoxesFormData;
  formId?: string;
  timestamp: number;
}

// Hook utility types
export interface HookUtilities {
  addFilter: <T extends SpiderBoxesHookName>(
    hookName: T,
    namespace: string,
    callback: SpiderBoxesHookTypes[T],
    priority?: number
  ) => void;

  addAction: <T extends SpiderBoxesHookName>(
    hookName: T,
    namespace: string,
    callback: SpiderBoxesHookTypes[T],
    priority?: number
  ) => void;

  applyFilters: <T extends SpiderBoxesHookName>(
    hookName: T,
    value: Parameters<SpiderBoxesHookTypes[T]>[0],
    ...args: any[]
  ) => ReturnType<SpiderBoxesHookTypes[T]>;

  doAction: <T extends SpiderBoxesHookName>(
    hookName: T,
    ...args: Parameters<SpiderBoxesHookTypes[T]>
  ) => void;

  removeFilter: (hookName: SpiderBoxesHookName, namespace: string) => boolean;
  removeAction: (hookName: SpiderBoxesHookName, namespace: string) => boolean;
}

export {};
