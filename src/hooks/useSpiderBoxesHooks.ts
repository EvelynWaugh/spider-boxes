import {
  addFilter,
  addAction,
  applyFilters,
  doAction,
  removeFilter,
  removeAction,
} from "./createHooks";
import type {FilterCallback, ActionCallback} from "./types";

// Re-export types for convenience
export type {FilterCallback, ActionCallback} from "./types";

/**
 * Utility class for managing WordPress hooks in Spider Boxes
 */
export class SpiderBoxesHooks {
  /**
   * Add a filter with better TypeScript support
   */
  static addFilter<T = any>(
    hookName: string,
    namespace: string,
    callback: FilterCallback<T>,
    priority: number = 10
  ): void {
    addFilter(hookName, `spider-boxes/${namespace}`, callback, priority);
  }

  /**
   * Add an action with better TypeScript support
   */
  static addAction(
    hookName: string,
    namespace: string,
    callback: ActionCallback,
    priority: number = 10
  ): void {
    addAction(hookName, `spider-boxes/${namespace}`, callback, priority);
  }
  /**
   * Apply filters with type safety
   */
  static applyFilters<T = any>(hookName: string, value: T, ...args: any[]): T {
    return applyFilters(hookName, value, ...args) as T;
  }

  /**
   * Execute actions
   */
  static doAction(hookName: string, ...args: any[]): void {
    doAction(hookName, ...args);
  }

  /**
   * Remove a filter
   */
  static removeFilter(hookName: string, namespace: string): boolean {
    return removeFilter(hookName, `spider-boxes/${namespace}`) !== undefined;
  }

  /**
   * Remove an action
   */
  static removeAction(hookName: string, namespace: string): boolean {
    return removeAction(hookName, `spider-boxes/${namespace}`) !== undefined;
  }
}

/**
 * Common hook names used throughout Spider Boxes
 */
export const SPIDER_BOXES_HOOKS = {
  // Component lifecycle hooks
  COMPONENT_MOUNT: "spider_boxes_component_mount",
  COMPONENT_UNMOUNT: "spider_boxes_component_unmount",

  // Content filters
  BEFORE_CONTENT: "spider_boxes_before_content",
  AFTER_CONTENT: "spider_boxes_after_content",
  FILTER_CONTENT: "spider_boxes_filter_content",

  // Field hooks
  BEFORE_FIELD_RENDER: "spider_boxes_before_field_render",
  AFTER_FIELD_RENDER: "spider_boxes_after_field_render",
  FIELD_VALUE_CHANGED: "spider_boxes_field_value_changed",

  // Section hooks
  BEFORE_SECTION_RENDER: "spider_boxes_before_section_render",
  AFTER_SECTION_RENDER: "spider_boxes_after_section_render",

  // Form hooks
  BEFORE_FORM_SUBMIT: "spider_boxes_before_form_submit",
  AFTER_FORM_SUBMIT: "spider_boxes_after_form_submit",
  FORM_VALIDATION: "spider_boxes_form_validation",

  // Admin hooks
  ADMIN_PAGE_LOADED: "spider_boxes_admin_page_loaded",
  ADMIN_SETTINGS_SAVED: "spider_boxes_admin_settings_saved",

  // Reviews hooks (WooCommerce integration)
  BEFORE_REVIEW_RENDER: "spider_boxes_before_review_render",
  AFTER_REVIEW_RENDER: "spider_boxes_after_review_render",
  REVIEW_STATUS_CHANGED: "spider_boxes_review_status_changed",
} as const;

/**
 * React hook for component lifecycle management with WordPress hooks
 */
export const useComponentLifecycle = (componentName: string) => {
  React.useEffect(() => {
    // Trigger mount action
    SpiderBoxesHooks.doAction(
      SPIDER_BOXES_HOOKS.COMPONENT_MOUNT,
      componentName
    );

    // Return cleanup function for unmount
    return () => {
      SpiderBoxesHooks.doAction(
        SPIDER_BOXES_HOOKS.COMPONENT_UNMOUNT,
        componentName
      );
    };
  }, [componentName]);
};

/**
 * React hook for applying filters to content
 */
export const useFilteredContent = <T = any>(
  hookName: string,
  initialValue: T
): T => {
  const [filteredValue, setFilteredValue] = React.useState<T>(initialValue);

  React.useEffect(() => {
    const result = SpiderBoxesHooks.applyFilters(hookName, initialValue);
    setFilteredValue(result);
  }, [hookName, initialValue]);

  return filteredValue;
};

// Import React for the hooks
import React from "react";

export default SpiderBoxesHooks;
