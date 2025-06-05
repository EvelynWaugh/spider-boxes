import {createHooks} from "@wordpress/hooks";

// Extend the global window interface to include our hooks
declare global {
  interface Window {
    spiderBoxesHooks?: ReturnType<typeof createHooks>;
  }
}

// Create a global hooks instance using WordPress hooks
if (!window.spiderBoxesHooks) {
  window.spiderBoxesHooks = createHooks();
}

// Export hooks functions from the WordPress hooks instance
export const {
  filters,
  addFilter,
  applyFilters,
  removeFilter,
  removeAllFilters,
  hasFilter,
  actions,
  addAction,
  doAction,
  removeAction,
  removeAllActions,
  hasAction,
} = window.spiderBoxesHooks;

// Export the hooks instance for direct access
export default window.spiderBoxesHooks;

// Hook for React components to use the hooks system
export const useHooks = () => {
  return window.spiderBoxesHooks!;
};
