import {Tabs, TabsContent, TabsList, TabsTrigger} from "@/components/ui/Tabs";
import {FieldTypesManager} from "@/components/FieldTypesManager";
import {FieldsManager} from "@/components/FieldsManager";
import {ComponentsManager} from "@/components/ComponentsManager";
import {SectionsManager} from "@/components/SectionsManager";

export function AdminApp() {
  return (
    <div className="spider-boxes-admin">
      <div className="spider-boxes-header">
        <h1 className="text-xl font-semibold text-gray-900">
          Spider Boxes Configuration
        </h1>
        <p className="mt-1 text-sm text-gray-600">
          Manage field types, fields, components, and sections for your custom
          meta boxes.
        </p>
      </div>

      <div className="spider-boxes-content">
        <Tabs defaultValue="fields" className="w-full">
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="fields">Fields</TabsTrigger>
            <TabsTrigger value="components">Components</TabsTrigger>
            <TabsTrigger value="sections">Sections</TabsTrigger>
            <TabsTrigger value="field-types">Field Types</TabsTrigger>
          </TabsList>

          <TabsContent value="fields" className="mt-6">
            <FieldsManager />
          </TabsContent>

          <TabsContent value="components" className="mt-6">
            <ComponentsManager />
          </TabsContent>

          <TabsContent value="sections" className="mt-6">
            <SectionsManager />
          </TabsContent>

          <TabsContent value="field-types" className="mt-6">
            <FieldTypesManager />
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}
