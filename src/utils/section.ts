// Section utility functions and type definitions

// Section - backend/API structure
export interface Section {
  id: string;
  type: string;
  title: string;
  description: string;
  context: string;
  screen?: string;
  settings: Record<string, any>;
  components: Record<string, any>;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
}

// SectionData - frontend form structure
export interface SectionData {
  id?: string;
  type: string;
  title: string;
  description?: string;
  context: string;
  screen?: string;
  settings?: Record<string, any>;
  components?: Record<string, any>;
  is_active?: boolean;
}

// SectionType - backend/API structure
export interface SectionType {
  id: string;
  name: string;
  type: string;
  class_name?: string;
  description: string;
  icon?: string;
  supports: string[];
  category?: string;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
}

// SectionTypeData - frontend form structure
export interface SectionTypeData {
  id: string;
  type: string;
  name: string;
  class_name?: string;
  description?: string;
  icon?: string;
  supports?: string[];
  category?: string;
  is_active?: boolean;
}

// Convert Section to SectionData (for editing)
export function convertSectionToSectionData(section: Section): SectionData {
  return {
    id: section.id,
    type: section.type,
    title: section.title,
    description: section.description,
    context: section.context,
    screen: section.screen,
    settings: section.settings || {},
    components: section.components || {},
    is_active: section.is_active ?? true,
  };
}

// Convert SectionData to Section (for API)
export function convertSectionDataToSection(sectionData: SectionData): Section {
  return {
    id: sectionData.id || "",
    type: sectionData.type,
    title: sectionData.title,
    description: sectionData.description || "",
    context: sectionData.context,
    screen: sectionData.screen,
    settings: sectionData.settings || {},
    components: sectionData.components || {},
    is_active: sectionData.is_active ?? true,
  };
}

// Convert SectionType to SectionTypeData (for editing)
export function convertSectionTypeToSectionTypeData(sectionType: SectionType): SectionTypeData {
  return {
    id: sectionType.id,
    type: sectionType.type,
    name: sectionType.name,
    class_name: sectionType.class_name,
    description: sectionType.description,
    icon: sectionType.icon,
    supports: sectionType.supports || [],
    category: sectionType.category,
    is_active: sectionType.is_active ?? true,
  };
}

// Convert SectionTypeData to SectionType (for API)
export function convertSectionTypeDataToSectionType(sectionTypeData: SectionTypeData): SectionType {
  return {
    id: sectionTypeData.id,
    name: sectionTypeData.name,
    type: sectionTypeData.type,
    class_name: sectionTypeData.class_name,
    description: sectionTypeData.description || "",
    icon: sectionTypeData.icon,
    supports: sectionTypeData.supports || [],
    category: sectionTypeData.category,
    is_active: sectionTypeData.is_active ?? true,
  };
}
