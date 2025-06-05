import React, {useState} from "react";
import {useQuery} from "@tanstack/react-query";
import {motion, AnimatePresence} from "framer-motion";
import {Button} from "./ui/Button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/Dialog";
import {useAPI} from "@/hooks/useAPI";
import {FieldRenderer} from "./FieldRenderer";

interface FieldType {
  type: string;
  label: string;
  description: string;
  supports: string[];
  category: string;
  icon?: string;
  defaultConfig?: Record<string, any>;
}

interface FieldPreview {
  config: {
    id: string;
    type: string;
    label: string;
    description?: string;
    placeholder?: string;
    required?: boolean;
    options?: Array<{label: string; value: string}>;
    [key: string]: any;
  };
  value?: any;
}

export const FieldTypesManager: React.FC = () => {
  const {get} = useAPI();

  const [selectedFieldType, setSelectedFieldType] = useState<FieldType | null>(
    null
  );
  const [isPreviewOpen, setIsPreviewOpen] = useState(false);
  const [previewConfig, setPreviewConfig] = useState<FieldPreview>({
    config: {
      id: "preview_field",
      type: "text",
      label: "Preview Field",
    },
  });
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("all");

  // Fetch field types
  const {data: fieldTypes = [], isLoading} = useQuery({
    queryKey: ["field-types"],
    queryFn: () => get("/field-types"),
  });

  const categories = React.useMemo(() => {
    const cats = fieldTypes.reduce((acc: string[], fieldType: FieldType) => {
      if (!acc.includes(fieldType.category)) {
        acc.push(fieldType.category);
      }
      return acc;
    }, []);
    return ["all", ...cats.sort()];
  }, [fieldTypes]);

  const filteredFieldTypes = React.useMemo(() => {
    return fieldTypes.filter((fieldType: FieldType) => {
      const matchesSearch =
        searchTerm === "" ||
        fieldType.label.toLowerCase().includes(searchTerm.toLowerCase()) ||
        fieldType.description
          .toLowerCase()
          .includes(searchTerm.toLowerCase()) ||
        fieldType.type.toLowerCase().includes(searchTerm.toLowerCase());

      const matchesCategory =
        selectedCategory === "all" || fieldType.category === selectedCategory;

      return matchesSearch && matchesCategory;
    });
  }, [fieldTypes, searchTerm, selectedCategory]);

  const handlePreviewField = (fieldType: FieldType) => {
    setSelectedFieldType(fieldType);
    setPreviewConfig({
      config: {
        id: "preview_field",
        type: fieldType.type,
        label: `Preview ${fieldType.label}`,
        description: fieldType.description,
        placeholder: `Enter ${fieldType.label.toLowerCase()}...`,
        required: false,
        ...fieldType.defaultConfig,
      },
      value: getDefaultValue(fieldType.type),
    });
    setIsPreviewOpen(true);
  };

  const getDefaultValue = (type: string) => {
    switch (type) {
      case "checkbox":
        return false;
      case "radio":
      case "select":
        return "";
      case "range":
        return 50;
      case "switcher":
        return false;
      case "number":
        return 0;
      case "date":
      case "datetime":
        return "";
      case "textarea":
      case "wysiwyg":
        return "";
      case "media":
        return null;
      default:
        return "";
    }
  };

  const getFieldIcon = (type: string) => {
    const icons: Record<string, string> = {
      text: "📝",
      textarea: "📄",
      number: "🔢",
      email: "📧",
      url: "🔗",
      password: "🔒",
      select: "📋",
      checkbox: "☑️",
      radio: "🔘",
      range: "🎚️",
      date: "📅",
      datetime: "🕐",
      file: "📎",
      media: "🖼️",
      wysiwyg: "📝",
      switcher: "🔀",
      button: "🔳",
      hidden: "👁️‍🗨️",
      repeater: "🔄",
      "react-select": "📋",
    };
    return icons[type] || "🔧";
  };

  const getCategoryIcon = (category: string) => {
    const icons: Record<string, string> = {
      input: "⌨️",
      selection: "📋",
      media: "🖼️",
      layout: "📐",
      advanced: "⚙️",
    };
    return icons[category] || "📁";
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="field-types-manager">
      <div className="field-types-header">
        <h3 className="text-lg font-semibold mb-4">Available Field Types</h3>

        {/* Search and Filter */}
        <div className="field-types-filters">
          <div className="search-wrapper">
            <input
              type="text"
              placeholder="Search field types..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="search-input"
            />
          </div>
          <div className="category-filter">
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="filter-select"
            >
              {categories.map((category) => (
                <option key={category} value={category}>
                  {category === "all"
                    ? "All Categories"
                    : category.charAt(0).toUpperCase() + category.slice(1)}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Field Type Grid */}
      <div className="field-types-grid">
        <AnimatePresence>
          {filteredFieldTypes.map((fieldType: FieldType) => (
            <motion.div
              key={fieldType.type}
              initial={{opacity: 0, y: 20}}
              animate={{opacity: 1, y: 0}}
              exit={{opacity: 0, y: -20}}
              transition={{duration: 0.2}}
              className="field-type-card"
            >
              <div className="field-type-header">
                <div className="field-type-icon">
                  {fieldType.icon || getFieldIcon(fieldType.type)}
                </div>
                <div className="field-type-info">
                  <h4 className="field-type-label">{fieldType.label}</h4>
                  <p className="field-type-name">{fieldType.type}</p>
                </div>
                <div className="field-type-category">
                  {getCategoryIcon(fieldType.category)} {fieldType.category}
                </div>
              </div>

              <div className="field-type-description">
                {fieldType.description}
              </div>

              {fieldType.supports && fieldType.supports.length > 0 && (
                <div className="field-type-supports">
                  <span className="supports-label">Supports:</span>
                  <div className="supports-list">
                    {fieldType.supports.map((feature) => (
                      <span key={feature} className="support-tag">
                        {feature}
                      </span>
                    ))}
                  </div>
                </div>
              )}

              <div className="field-type-actions">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handlePreviewField(fieldType)}
                >
                  Preview
                </Button>
              </div>
            </motion.div>
          ))}
        </AnimatePresence>
      </div>

      {filteredFieldTypes.length === 0 && (
        <div className="empty-state">
          <div className="empty-state-icon">🔍</div>
          <h3 className="empty-state-title">No field types found</h3>
          <p className="empty-state-description">
            Try adjusting your search or filter criteria.
          </p>
        </div>
      )}

      {/* Categories Overview */}
      <div className="categories-overview">
        <h4 className="categories-title">Field Categories</h4>
        <div className="categories-grid">
          {categories
            .filter((cat) => cat !== "all")
            .map((category) => {
              const categoryFields = fieldTypes.filter(
                (ft: FieldType) => ft.category === category
              );
              return (
                <div key={category} className="category-card">
                  <div className="category-icon">
                    {getCategoryIcon(category)}
                  </div>
                  <div className="category-info">
                    <h5 className="category-name">
                      {category.charAt(0).toUpperCase() + category.slice(1)}
                    </h5>
                    <p className="category-count">
                      {categoryFields.length} field
                      {categoryFields.length !== 1 ? "s" : ""}
                    </p>
                  </div>
                  <div className="category-fields">
                    {categoryFields.slice(0, 3).map((field: FieldType) => (
                      <span key={field.type} className="category-field-preview">
                        {field.label}
                      </span>
                    ))}
                    {categoryFields.length > 3 && (
                      <span className="category-field-more">
                        +{categoryFields.length - 3} more
                      </span>
                    )}
                  </div>
                </div>
              );
            })}
        </div>
      </div>

      {/* Field Preview Dialog */}
      <Dialog open={isPreviewOpen} onOpenChange={setIsPreviewOpen}>
        <DialogContent size="lg">
          <DialogHeader>
            <DialogTitle>Field Preview: {selectedFieldType?.label}</DialogTitle>
          </DialogHeader>

          <div className="field-preview">
            {selectedFieldType && (
              <div className="preview-info">
                <div className="preview-meta">
                  <div className="preview-type">
                    <span className="meta-label">Type:</span>
                    <code>{selectedFieldType.type}</code>
                  </div>
                  <div className="preview-category">
                    <span className="meta-label">Category:</span>
                    <span>{selectedFieldType.category}</span>
                  </div>
                </div>
                <div className="preview-description">
                  {selectedFieldType.description}
                </div>
              </div>
            )}

            <div className="preview-field">
              <h4 className="preview-title">Live Preview</h4>
              <div className="preview-container">
                <FieldRenderer
                  config={previewConfig.config}
                  value={previewConfig.value}
                  onChange={(value) =>
                    setPreviewConfig((prev) => ({...prev, value}))
                  }
                />
              </div>
            </div>

            {previewConfig.value !== undefined &&
              previewConfig.value !== "" && (
                <div className="preview-output">
                  <h4 className="output-title">Current Value</h4>
                  <pre className="output-value">
                    {JSON.stringify(previewConfig.value, null, 2)}
                  </pre>
                </div>
              )}

            <div className="preview-actions">
              <Button variant="outline" onClick={() => setIsPreviewOpen(false)}>
                Close
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
};
