import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { motion } from "framer-motion";
import { PlusIcon, PencilIcon, TrashIcon, SearchIcon } from "@/components/icons";
import { Button } from "./ui/Button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/Dialog";
import { SectionForm } from "./forms/SectionForm";
import { useAPI } from "@/hooks/useAPI";
import { doAction, applyFilters } from "@/hooks/createHooks";
import { type Section } from "@/utils/section";

export function SectionsManager() {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingSection, setEditingSection] = useState<Section | null>(null);
  const [searchTerm, setSearchTerm] = useState("");
  const queryClient = useQueryClient();
  const api = useAPI();

  const { data: sections = [], isLoading } = useQuery({
    queryKey: ["sections"],
    queryFn: () => api.get("/sections"),
  });

  const createSectionMutation = useMutation({
    mutationFn: (sectionData: Partial<Section>) => api.post("/sections", sectionData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["sections"] });
      setIsFormOpen(false);
      setEditingSection(null);
      doAction("spiderBoxes.sectionCreated");
    },
    onError: (error) => {
      console.error("Error creating section:", error);
    },
  });

  const updateSectionMutation = useMutation({
    mutationFn: ({ id, ...sectionData }: Partial<Section> & { id: string }) => api.put(`/sections/${id}`, sectionData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["sections"] });
      setIsFormOpen(false);
      setEditingSection(null);
      doAction("spiderBoxes.sectionUpdated");
    },
    onError: (error) => {
      console.error("Error updating section:", error);
    },
  });

  const deleteSectionMutation = useMutation({
    mutationFn: (id: string) => api.del(`/sections/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["sections"] });
      doAction("spiderBoxes.sectionDeleted");
    },
  });

  const handleCreateSection = () => {
    setEditingSection(null);
    setIsFormOpen(true);
    createSectionMutation.reset();
    updateSectionMutation.reset();
  };

  const handleEditSection = (section: Section) => {
    setEditingSection(section);
    setIsFormOpen(true);
    createSectionMutation.reset();
    updateSectionMutation.reset();
  };

  const handleCloseForm = () => {
    setIsFormOpen(false);
    setEditingSection(null);
    createSectionMutation.reset();
    updateSectionMutation.reset();
  };

  const handleDeleteSection = (id: string) => {
    if (confirm("Are you sure you want to delete this section?")) {
      deleteSectionMutation.mutate(id);
    }
  };

  const handleFormSubmit = (sectionData: Section) => {
    console.log("Form submitted with data:", sectionData);

    const requiredFields = ["type", "title"];
    const missingFields = requiredFields.filter((field) => !sectionData[field as keyof Section]);

    if (missingFields.length > 0) {
      console.error(`Missing required fields: ${missingFields.join(", ")}`);
      return;
    }

    const processedSection = applyFilters("spiderBoxes.sectionToSave", sectionData, editingSection) as Section;

    if (editingSection && editingSection.id !== "new") {
      console.log("Updating existing section:", editingSection.id);
      updateSectionMutation.mutate({ ...processedSection, id: editingSection.id });
    } else {
      console.log("Creating new section");
      createSectionMutation.mutate(processedSection);
    }
  };

  const getErrorMessage = (error: any): string => {
    if (!error) return "";
    if (typeof error === "string") return error;
    if (error.message) return error.message;
    if (error.response?.data?.message) return error.response.data.message;
    if (error.response?.statusText) return error.response.statusText;
    return "An unexpected error occurred";
  };

  // Filter sections based on search term
  const filteredSections = sections.filter(
    (section: Section) =>
      section.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
      section.type.toLowerCase().includes(searchTerm.toLowerCase()) ||
      section.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
      section.context.toLowerCase().includes(searchTerm.toLowerCase()),
  );

  // Apply filters for extensibility
  const displaySections = applyFilters("spiderBoxes.sectionsTableData", filteredSections) as Section[];

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="spider-boxes-sections-manager">
      {/* Header */}

      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-medium text-gray-900">Sections</h2>
          <p className="text-sm text-gray-600">Manage sections that group fields and components together.</p>
        </div>
        <Button onClick={handleCreateSection} className="spider-boxes-button">
          <PlusIcon className="w-4 h-4 mr-2" />
          Add Section
        </Button>
      </div>

      {displaySections.length === 0 ? (
        <div className="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
          <p className="text-gray-500 mb-4">No components created yet.</p>
          <Button onClick={handleCreateSection} className="spider-boxes-create-button">
            <PlusIcon className="w-4 h-4 mr-2" />
            Create your first section
          </Button>
        </div>
      ) : (
        <div className="spider-boxes-table-container">
          {/* Search */}
          <div className="spider-boxes-search-container">
            <div className="spider-boxes-search-wrapper">
              <SearchIcon className="spider-boxes-search-icon" />
              <input
                type="text"
                placeholder="Search sections..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="spider-boxes-search-input"
              />
            </div>
          </div>
          <div className="spider-boxes-table">
            <div className="spider-boxes-table-header">
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Section</div>
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Type</div>
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Description</div>
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Context</div>
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Components</div>
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Status</div>
              <div className="spider-boxes-table-cell spider-boxes-table-header-cell">Actions</div>
            </div>

            <div className="spider-boxes-table-body">
              {displaySections.map((section: Section) => (
                <motion.div key={section.id} initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="spider-boxes-table-row">
                  <div className="spider-boxes-table-cell">
                    <div className="flex items-center space-x-3">
                      <div className="spider-boxes-section-icon">{section.type === "form" ? "📋" : "📄"}</div>
                      <div>
                        <div className="font-medium text-gray-900">{section.title}</div>
                        <div className="text-sm text-gray-500">ID: {section.id}</div>
                      </div>
                    </div>
                  </div>

                  <div className="spider-boxes-table-cell">
                    <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize">
                      {section.type}
                    </span>
                  </div>

                  <div className="spider-boxes-table-cell">
                    <div className="text-sm text-gray-900 max-w-xs truncate">{section.description || "—"}</div>
                  </div>

                  <div className="spider-boxes-table-cell">
                    <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                      {section.context}
                    </span>
                    {section.screen && <div className="text-xs text-gray-500 mt-1">Screen: {section.screen}</div>}
                  </div>

                  <div className="spider-boxes-table-cell">
                    <div className="text-sm text-gray-900">{Object.keys(section.components || {}).length} components</div>
                  </div>

                  <div className="spider-boxes-table-cell">
                    <span
                      className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                        section.is_active ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800"
                      }`}
                    >
                      {section.is_active ? "Active" : "Inactive"}
                    </span>
                  </div>

                  <div className="spider-boxes-table-cell">
                    <div className="flex items-center space-x-2">
                      <Button variant="outline" size="sm" onClick={() => handleEditSection(section)} className="spider-boxes-edit-button">
                        <PencilIcon className="spider-boxes-icon" />
                        Edit
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleDeleteSection(section.id)}
                        className="spider-boxes-delete-button"
                      >
                        <TrashIcon className="spider-boxes-icon" />
                        Delete
                      </Button>
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Section Form Dialog */}
      <Dialog open={isFormOpen} onOpenChange={handleCloseForm}>
        <DialogContent size="lg" className="spider-boxes-dialog">
          <DialogHeader>
            <DialogTitle>{editingSection ? "Edit Section" : "Create Section"}</DialogTitle>
          </DialogHeader>

          <SectionForm
            section={editingSection || undefined}
            onSave={handleFormSubmit}
            onCancel={handleCloseForm}
            error={getErrorMessage(createSectionMutation.error || updateSectionMutation.error)}
            isLoading={createSectionMutation.isPending || updateSectionMutation.isPending}
          />
        </DialogContent>
      </Dialog>
    </div>
  );
}
