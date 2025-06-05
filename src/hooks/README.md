# Spider Boxes Hooks System

This directory contains the WordPress hooks integration for Spider Boxes, using the official `@wordpress/hooks` package.

## Overview

The hooks system provides a way to extend and customize Spider Boxes functionality using WordPress-style filters and actions. This follows the same pattern as WordPress core hooks but works in the React frontend environment.

## Files

- `createHooks.ts` - Main hooks setup using `@wordpress/hooks`
- `useSpiderBoxesHooks.ts` - Utility class and React hooks for easier integration
- `README.md` - This documentation file

## Basic Usage

### Import the hooks

```typescript
import { addFilter, addAction, applyFilters, doAction } from '../hooks/createHooks';
// OR use the utility class
import { SpiderBoxesHooks, SPIDER_BOXES_HOOKS } from '../hooks/useSpiderBoxesHooks';
```

### Adding Filters

```typescript
// Basic filter
addFilter('spider_boxes_content', 'my-plugin/modify-content', (content) => {
  return `Modified: ${content}`;
});

// Using the utility class (recommended)
SpiderBoxesHooks.addFilter('spider_boxes_content', 'my-plugin/modify-content', (content) => {
  return `Modified: ${content}`;
});
```

### Adding Actions

```typescript
// Basic action
addAction('spider_boxes_component_mount', 'my-plugin/log-mount', (componentName) => {
  console.log(`Component ${componentName} mounted`);
});

// Using the utility class (recommended)
SpiderBoxesHooks.addAction('spider_boxes_component_mount', 'my-plugin/log-mount', (componentName) => {
  console.log(`Component ${componentName} mounted`);
});
```

### Applying Filters

```typescript
// Apply filters to modify content
const modifiedContent = applyFilters('spider_boxes_content', 'Original content');

// Using utility class
const modifiedContent = SpiderBoxesHooks.applyFilters('spider_boxes_content', 'Original content');
```

### Triggering Actions

```typescript
// Trigger an action
doAction('spider_boxes_component_mount', 'MyComponent');

// Using utility class
SpiderBoxesHooks.doAction('spider_boxes_component_mount', 'MyComponent');
```

## React Hooks

### useComponentLifecycle

Automatically triggers mount/unmount actions for React components:

```typescript
import { useComponentLifecycle } from '../hooks/useSpiderBoxesHooks';

const MyComponent = () => {
  useComponentLifecycle('MyComponent');
  
  return <div>My Component</div>;
};
```

### useFilteredContent

Apply filters to content with automatic re-rendering:

```typescript
import { useFilteredContent } from '../hooks/useSpiderBoxesHooks';

const MyComponent = () => {
  const filteredTitle = useFilteredContent('spider_boxes_filter_content', 'Original Title');
  
  return <h1>{filteredTitle}</h1>;
};
```

## Predefined Hook Names

Use the `SPIDER_BOXES_HOOKS` constant for consistent hook naming:

```typescript
import { SPIDER_BOXES_HOOKS } from '../hooks/useSpiderBoxesHooks';

// Component lifecycle
SPIDER_BOXES_HOOKS.COMPONENT_MOUNT
SPIDER_BOXES_HOOKS.COMPONENT_UNMOUNT

// Content filters
SPIDER_BOXES_HOOKS.BEFORE_CONTENT
SPIDER_BOXES_HOOKS.AFTER_CONTENT
SPIDER_BOXES_HOOKS.FILTER_CONTENT

// Field hooks
SPIDER_BOXES_HOOKS.BEFORE_FIELD_RENDER
SPIDER_BOXES_HOOKS.AFTER_FIELD_RENDER
SPIDER_BOXES_HOOKS.FIELD_VALUE_CHANGED

// Section hooks
SPIDER_BOXES_HOOKS.BEFORE_SECTION_RENDER
SPIDER_BOXES_HOOKS.AFTER_SECTION_RENDER

// Form hooks
SPIDER_BOXES_HOOKS.BEFORE_FORM_SUBMIT
SPIDER_BOXES_HOOKS.AFTER_FORM_SUBMIT
SPIDER_BOXES_HOOKS.FORM_VALIDATION
```

## Best Practices

1. **Use descriptive namespaces**: Always prefix your hook callbacks with your plugin/theme name
2. **Type safety**: Use TypeScript interfaces for better type checking
3. **Cleanup**: Remove hooks when components unmount to prevent memory leaks
4. **Consistent naming**: Use the predefined hook names when possible
5. **Documentation**: Document your custom hooks for other developers

## Example Components

- `ExampleHooksUsage.tsx` - Basic usage examples
- `FieldComponent.tsx` - Advanced field component with hooks integration

## Integration with WordPress

This hooks system is designed to work alongside WordPress server-side hooks. You can trigger PHP actions from React and vice versa through the REST API.

## Advanced Usage

### Custom Hook Types

```typescript
// Define custom hook types
type MyCustomFilter = (content: string, context: any) => string;
type MyCustomAction = (data: any) => void;

// Use with type safety
SpiderBoxesHooks.addFilter<string>('my_custom_filter', 'namespace', (content: string) => {
  return content.toUpperCase();
});
```

### Hook Priorities

```typescript
// Higher priority (runs first)
SpiderBoxesHooks.addFilter('hook_name', 'namespace', callback, 5);

// Lower priority (runs later)
SpiderBoxesHooks.addFilter('hook_name', 'namespace', callback, 15);
```

### Removing Hooks

```typescript
// Remove a specific filter
SpiderBoxesHooks.removeFilter('hook_name', 'namespace');

// Remove a specific action
SpiderBoxesHooks.removeAction('hook_name', 'namespace');
```
