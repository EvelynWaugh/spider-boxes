// Component - backend/API structure
export interface Component {
  id: string;
  type: string;
  title: string;
  description: string;
  parent_id?: string;
  section_id?: string;
  context: string;
  settings: Record<string, any>;
  children: Record<string, any>;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
}

// ComponentData - frontend form structure
export interface ComponentData {
  id?: string;
  type: string;
  title: string;
  description?: string;
  parent_id?: string;
  section_id?: string;
  context?: string;
  settings?: Record<string, any>;
  children?: Record<string, any>;
  is_active?: boolean;
}

// Convert from backend Component to frontend ComponentData
export function convertComponentToComponentData(component: Component | null): ComponentData | null {
  if (!component) return null;

  return {
    id: component.id,
    type: component.type,
    title: component.title,
    description: component.description || "",
    parent_id: component.parent_id,
    section_id: component.section_id,
    context: component.context || "default",
    settings: component.settings || {},
    children: component.children || {},
    is_active: component.is_active ?? true,
  };
}

// Convert from frontend ComponentData to backend Component
export function convertComponentDataToComponent(componentData: ComponentData): Partial<Component> {
  return {
    id: componentData.id,
    type: componentData.type,
    title: componentData.title,
    description: componentData.description || "",
    parent_id: componentData.parent_id,
    section_id: componentData.section_id,
    context: componentData.context || "default",
    settings: componentData.settings || {},
    children: componentData.children || {},
    is_active: componentData.is_active ?? true,
  };
}
