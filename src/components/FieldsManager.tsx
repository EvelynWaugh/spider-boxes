import {useState} from "react";
import {useQuery, useMutation, useQueryClient} from "@tanstack/react-query";
import {motion} from "framer-motion";
import {PlusIcon, PencilIcon, TrashIcon} from "@/components/icons";
import {Button} from "./ui/Button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/Dialog";
import {FieldForm, type FieldData} from "@/components/forms/FieldForm";
import {useAPI} from "@/hooks/useAPI";
import {doAction} from "@/hooks/createHooks";

interface Field {
  id: string;
  type: string;
  title: string;
  description: string;
  parent: string;
  value: any;
  context: string;
}

// Convert Field to FieldData format
const convertFieldToFieldData = (
  field: Field | null
): FieldData | undefined => {
  if (!field) return undefined;
  return {
    id: field.id,
    type: field.type,
    label: field.title,
    description: field.description,
    default_value: field.value,
  };
};

// Convert FieldData to Field format
const convertFieldDataToField = (fieldData: FieldData): Partial<Field> => {
  const field: Partial<Field> = {
    id: fieldData.id, // Always include the ID (generated or existing)
    type: fieldData.type,
    title: fieldData.label,
    description: fieldData.description || "",
    value: fieldData.default_value,
    parent: "", // Default empty parent
    context: "default", // Default context
  };

  return field;
};

export function FieldsManager() {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingField, setEditingField] = useState<Field | null>(null);
  const queryClient = useQueryClient();
  const api = useAPI();
  const {data: fields = [], isLoading} = useQuery({
    queryKey: ["fields"],
    queryFn: () => api.get("/fields"),
  });
  const createFieldMutation = useMutation({
    mutationFn: (fieldData: Partial<Field>) => api.post("/fields", fieldData),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ["fields"]});
      setIsFormOpen(false);
      setEditingField(null); // Reset editing field on success
      doAction("spiderBoxes.fieldCreated");
    },
    onError: (error) => {
      console.error("Error creating field:", error);
    },
  });
  const updateFieldMutation = useMutation({
    mutationFn: ({id, ...fieldData}: Partial<Field> & {id: string}) =>
      api.put(`/fields/${id}`, fieldData),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ["fields"]});
      setIsFormOpen(false);
      setEditingField(null);
      doAction("spiderBoxes.fieldUpdated");
    },
    onError: (error) => {
      console.error("Error updating field:", error);
    },
  });

  const deleteFieldMutation = useMutation({
    mutationFn: (id: string) => api.del(`/fields/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ["fields"]});
      doAction("spiderBoxes.fieldDeleted");
    },
  });
  const handleCreateField = () => {
    setEditingField(null);
    setIsFormOpen(true);
    // Reset any previous errors
    createFieldMutation.reset();
    updateFieldMutation.reset();
    console.log("Opening dialog for new field");
  };

  const handleEditField = (field: Field) => {
    setEditingField(field);
    setIsFormOpen(true);
    // Reset any previous errors
    createFieldMutation.reset();
    updateFieldMutation.reset();
  };

  const handleCloseForm = () => {
    setIsFormOpen(false);
    setEditingField(null);
    // Reset any errors when closing
    createFieldMutation.reset();
    updateFieldMutation.reset();
  };

  const handleDeleteField = (id: string) => {
    if (confirm("Are you sure you want to delete this field?")) {
      deleteFieldMutation.mutate(id);
    }
  };
  const handleFormSubmit = (fieldData: FieldData) => {
    console.log("Form submitted with data:", fieldData);

    const fieldToSave = convertFieldDataToField(fieldData);
    console.log("Converted field data:", fieldToSave);

    // Ensure we have all required fields
    if (!fieldToSave.id || !fieldToSave.title || !fieldToSave.type) {
      console.error("Missing required fields: id, title and type are required");
      return;
    }

    if (editingField) {
      console.log("Updating existing field:", editingField.id);
      updateFieldMutation.mutate({...fieldToSave, id: editingField.id});
    } else {
      console.log("Creating new field");
      createFieldMutation.mutate(fieldToSave);
    }
  };

  // Helper function to get error message
  const getErrorMessage = (error: any): string => {
    if (!error) return "";

    if (typeof error === "string") return error;

    if (error.message) return error.message;

    if (error.response?.data?.message) return error.response.data.message;

    if (error.response?.statusText) return error.response.statusText;

    return "An unexpected error occurred";
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-32">
        <div className="spider-boxes-loading"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-medium text-gray-900">Fields</h2>
          <p className="text-sm text-gray-600">
            Manage individual fields that can be used in components.
          </p>
        </div>
        <Button onClick={handleCreateField} className="spider-boxes-button">
          <PlusIcon className="w-4 h-4 mr-2" />
          Add Field
        </Button>
      </div>
      {fields.length === 0 ? (
        <div className="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
          <p className="text-gray-500 mb-4">No fields created yet.</p>
          <Button onClick={handleCreateField} className="spider-boxes-button">
            <PlusIcon className="w-4 h-4 mr-2" />
            Create your first field
          </Button>
        </div>
      ) : (
        <div className="spider-boxes-table">
          <div className="spider-boxes-table-header">
            <div className="spider-boxes-table-header-cell">Field ID</div>
            <div className="spider-boxes-table-header-cell">Title</div>
            <div className="spider-boxes-table-header-cell">Type</div>
            <div className="spider-boxes-table-header-cell">Parent</div>
            <div className="spider-boxes-table-header-cell">Context</div>
            <div className="spider-boxes-table-header-cell">Actions</div>
          </div>
          <div className="divide-y divide-gray-200">
            {fields.map((field: Field) => (
              <motion.div
                key={field.id}
                layout
                initial={{opacity: 0}}
                animate={{opacity: 1}}
                exit={{opacity: 0}}
                className="spider-boxes-table-row"
              >
                <div className="spider-boxes-table-cell font-mono text-xs bg-gray-50">
                  {field.id}
                </div>
                <div className="spider-boxes-table-cell font-medium">
                  {field.title || field.id}
                </div>
                <div className="spider-boxes-table-cell">
                  <span className="spider-boxes-badge spider-boxes-badge-success">
                    {field.type}
                  </span>
                </div>
                <div className="spider-boxes-table-cell text-gray-500">
                  {field.parent || "—"}
                </div>
                <div className="spider-boxes-table-cell text-gray-500">
                  {field.context}
                </div>
                <div className="spider-boxes-table-cell">
                  <div className="flex space-x-2">
                    <button
                      onClick={() => handleEditField(field)}
                      className="text-primary-600 hover:text-primary-900"
                      title="Edit field"
                    >
                      <PencilIcon className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => handleDeleteField(field.id)}
                      className="text-red-600 hover:text-red-900"
                      title="Delete field"
                    >
                      <TrashIcon className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      )}{" "}
      <Dialog open={isFormOpen} onOpenChange={setIsFormOpen}>
        <DialogContent size="lg">
          <DialogHeader>
            <DialogTitle>
              {editingField ? "Edit Field" : "Create New Field"}
            </DialogTitle>
          </DialogHeader>
          <FieldForm
            field={convertFieldToFieldData(editingField)}
            onSave={handleFormSubmit}
            onCancel={handleCloseForm}
            error={
              getErrorMessage(createFieldMutation.error) ||
              getErrorMessage(updateFieldMutation.error)
            }
            isLoading={
              createFieldMutation.isPending || updateFieldMutation.isPending
            }
          />
        </DialogContent>
      </Dialog>
    </div>
  );
}
