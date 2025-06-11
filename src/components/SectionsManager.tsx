import React, { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { motion, AnimatePresence } from "framer-motion";
import { Button } from "./ui/Button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "./ui/Dialog";
import { useAPI } from "../hooks/useAPI";
import { FieldRenderer } from "./FieldRenderer";

interface Section {
  id: string;
  type: string;
  title: string;
  description?: string;
  context: string;
  screen?: string;
  settings?: Record<string, any>;
  components?: Record<string, any>;
  sort_order?: number;
  is_active?: boolean;
  capability: string;
  created_at?: string;
  updated_at?: string;
}

interface SectionType {
  id: string;
  name: string;
  class_name: string;
  category: string;
  description: string;
  supports: string[];
}

export const SectionsManager: React.FC = () => {
  const queryClient = useQueryClient();
  const { get, post, patch, del } = useAPI();

  const [selectedSection, setSelectedSection] = useState<Section | null>(null);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [isCreateMode, setIsCreateMode] = useState(false);
  const [sectionForm, setSectionForm] = useState<Partial<Section>>({});

  // Fetch sections
  const { data: sections = [], isLoading: sectionsLoading } = useQuery({
    queryKey: ["sections"],
    queryFn: () => get("/sections"),
  });
  // Fetch section types
  const { data: sectionTypesResponse = {}, isLoading: typesLoading } = useQuery({
    queryKey: ["section-types"],
    queryFn: () => get("/section-types"),
  });

  // Extract section types from the response and convert to array format
  const sectionTypes = Object.entries(sectionTypesResponse.section_types || {}).map(([id, type]: [string, any]) => ({
    id,
    name: type.class?.split("\\").pop() || id,
    class_name: type.class || "",
    category: type.category || "general",
    description: type.description || "",
    supports: type.supports || [],
  }));

  // Create section mutation
  const createSectionMutation = useMutation({
    mutationFn: (sectionData: Partial<Section>) => post("/sections", sectionData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["sections"] });
      setIsDialogOpen(false);
      setSectionForm({});
    },
  });

  // Update section mutation
  const updateSectionMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<Section> }) => patch(`/sections/${id}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["sections"] });
      setIsDialogOpen(false);
      setSelectedSection(null);
    },
  });

  // Delete section mutation
  const deleteSectionMutation = useMutation({
    mutationFn: (id: string) => del(`/sections/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["sections"] });
    },
  });
  const handleCreateSection = () => {
    setIsCreateMode(true);
    setSectionForm({
      type: "section",
      title: "",
      description: "",
      context: "default",
      screen: "",
      capability: "manage_options",
      settings: {},
      components: {},
      sort_order: 0,
      is_active: true,
    });
    setIsDialogOpen(true);
  };

  const handleEditSection = (section: Section) => {
    setIsCreateMode(false);
    setSelectedSection(section);
    setSectionForm(section);
    setIsDialogOpen(true);
  };

  const handleSaveSection = () => {
    if (isCreateMode) {
      createSectionMutation.mutate(sectionForm);
    } else if (selectedSection) {
      updateSectionMutation.mutate({
        id: selectedSection.id,
        data: sectionForm,
      });
    }
  };

  const handleDeleteSection = (id: string) => {
    if (confirm("Are you sure you want to delete this section?")) {
      deleteSectionMutation.mutate(id);
    }
  };
  const updateFormField = (field: string, value: any) => {
    setSectionForm((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const updateFormSettings = (key: string, value: any) => {
    setSectionForm((prev) => ({
      ...prev,
      settings: {
        ...prev.settings,
        [key]: value,
      },
    }));
  };

  const getSectionIcon = (type: string) => {
    const icons: Record<string, string> = {
      section: "📝",
      form: "📋",
    };
    return icons[type] || "📄";
  };
  if (sectionsLoading || typesLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="sections-manager">
      <div className="sections-header">
        <h3 className="text-lg font-semibold mb-4">Sections</h3>
        <Button onClick={handleCreateSection}>Create Section</Button>
      </div>

      <div className="sections-grid">
        <AnimatePresence>
          {sections.map((section: Section) => (
            <motion.div
              key={section.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -20 }}
              transition={{ duration: 0.2 }}
              className="section-card"
            >
              <div className="section-card-header">
                <div className="section-icon">{getSectionIcon(section.type)}</div>
                <div className="section-info">
                  <h4 className="section-title">{section.title}</h4>
                  <p className="section-type">{section.type}</p>
                  {section.description && <p className="section-description">{section.description}</p>}
                </div>
              </div>{" "}
              <div className="section-meta">
                <span className="section-context">Context: {section.context}</span>
                <span className="section-capability">Capability: {section.capability}</span>
                <span className="section-component-count">{Object.keys(section.components || {}).length} components</span>
              </div>
              <div className="section-actions">
                <Button variant="outline" size="sm" onClick={() => handleEditSection(section)}>
                  Edit
                </Button>
                <Button variant="outline" size="sm" onClick={() => handleDeleteSection(section.id)}>
                  Delete
                </Button>
              </div>
            </motion.div>
          ))}
        </AnimatePresence>
      </div>

      {sections.length === 0 && (
        <div className="empty-state">
          <div className="empty-state-icon">📄</div>
          <h3 className="empty-state-title">No sections yet</h3>
          <p className="empty-state-description">Create your first section to get started.</p>
          <Button onClick={handleCreateSection}>Create Section</Button>
        </div>
      )}

      {/* Section Form Dialog */}
      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent size="lg">
          <DialogHeader>
            <DialogTitle>{isCreateMode ? "Create Section" : "Edit Section"}</DialogTitle>
          </DialogHeader>

          <div className="section-form">
            <div className="form-grid">
              <FieldRenderer
                config={{
                  id: "title",
                  type: "text",
                  label: "Section Title",
                  required: true,
                  placeholder: "Enter section title",
                }}
                value={sectionForm.title || ""}
                onChange={(value) => updateFormField("title", value)}
              />

              <FieldRenderer
                config={{
                  id: "type",
                  type: "select",
                  label: "Section Type",
                  required: true,
                  options: sectionTypes.map((type: SectionType) => ({
                    label: type.name,
                    value: type.id,
                  })),
                }}
                value={sectionForm.type || ""}
                onChange={(value) => updateFormField("type", value)}
              />

              <FieldRenderer
                config={{
                  id: "description",
                  type: "textarea",
                  label: "Description",
                  placeholder: "Enter section description",
                  rows: 3,
                }}
                value={sectionForm.description || ""}
                onChange={(value) => updateFormField("description", value)}
              />

              <FieldRenderer
                config={{
                  id: "context",
                  type: "select",
                  label: "Context",
                  required: true,
                  options: [
                    { label: "Default", value: "default" },
                    { label: "Post Edit", value: "post_edit" },
                    { label: "User Profile", value: "user_profile" },
                    { label: "Settings", value: "settings" },
                    { label: "WooCommerce Product", value: "wc_product" },
                  ],
                }}
                value={sectionForm.context || "default"}
                onChange={(value) => updateFormField("context", value)}
              />

              <FieldRenderer
                config={{
                  id: "screen",
                  type: "text",
                  label: "Screen (Optional)",
                  placeholder: "e.g., post, page, product",
                  description: "Limit section to specific admin screens",
                }}
                value={sectionForm.screen || ""}
                onChange={(value) => updateFormField("screen", value)}
              />

              <FieldRenderer
                config={{
                  id: "capability",
                  type: "select",
                  label: "Required Capability",
                  required: true,
                  options: [
                    { label: "Manage Options", value: "manage_options" },
                    { label: "Edit Posts", value: "edit_posts" },
                    { label: "Edit Pages", value: "edit_pages" },
                    { label: "Edit Users", value: "edit_users" },
                    { label: "Manage WooCommerce", value: "manage_woocommerce" },
                  ],
                }}
                value={sectionForm.capability || "manage_options"}
                onChange={(value) => updateFormField("capability", value)}
              />
            </div>

            {/* Section Type Specific Configuration */}
            {sectionForm.type && (
              <div className="section-config">
                <h4 className="config-title">Section Configuration</h4>{" "}
                {sectionForm.type === "section" && (
                  <div className="config-grid">
                    <FieldRenderer
                      config={{
                        id: "collapsible",
                        type: "switcher",
                        label: "Collapsible",
                        description: "Allow users to collapse this section",
                      }}
                      value={sectionForm.settings?.collapsible || false}
                      onChange={(value) => updateFormSettings("collapsible", value)}
                    />
                    <FieldRenderer
                      config={{
                        id: "collapsed",
                        type: "switcher",
                        label: "Start Collapsed",
                        description: "Start with the section collapsed",
                      }}
                      value={sectionForm.settings?.collapsed || false}
                      onChange={(value) => updateFormSettings("collapsed", value)}
                    />
                  </div>
                )}
                {sectionForm.type === "form" && (
                  <div className="config-grid">
                    <FieldRenderer
                      config={{
                        id: "method",
                        type: "select",
                        label: "Form Method",
                        options: [
                          { label: "POST", value: "post" },
                          { label: "GET", value: "get" },
                        ],
                      }}
                      value={sectionForm.settings?.method || "post"}
                      onChange={(value) => updateFormSettings("method", value)}
                    />
                    <FieldRenderer
                      config={{
                        id: "action",
                        type: "text",
                        label: "Form Action URL",
                        placeholder: "Leave empty for current page",
                      }}
                      value={sectionForm.settings?.action || ""}
                      onChange={(value) => updateFormSettings("action", value)}
                    />
                    <FieldRenderer
                      config={{
                        id: "ajax",
                        type: "switcher",
                        label: "AJAX Submission",
                        description: "Submit form via AJAX",
                      }}
                      value={sectionForm.settings?.ajax || false}
                      onChange={(value) => updateFormSettings("ajax", value)}
                    />
                    <FieldRenderer
                      config={{
                        id: "submit_text",
                        type: "text",
                        label: "Submit Button Text",
                        placeholder: "Submit",
                      }}
                      value={sectionForm.settings?.submit_text || "Submit"}
                      onChange={(value) => updateFormSettings("submit_text", value)}
                    />
                    <FieldRenderer
                      config={{
                        id: "nonce_action",
                        type: "text",
                        label: "Nonce Action",
                        placeholder: "Enter nonce action for security",
                      }}
                      value={sectionForm.settings?.nonce_action || ""}
                      onChange={(value) => updateFormSettings("nonce_action", value)}
                    />
                  </div>
                )}
              </div>
            )}

            <div className="form-actions">
              <Button variant="outline" onClick={() => setIsDialogOpen(false)}>
                Cancel
              </Button>{" "}
              <Button onClick={handleSaveSection} disabled={createSectionMutation.isPending || updateSectionMutation.isPending}>
                {isCreateMode ? "Create Section" : "Save Changes"}
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
};
