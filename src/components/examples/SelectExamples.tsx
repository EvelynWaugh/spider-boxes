import React, {useState} from "react";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
  SimpleSelect,
  GroupedSelect,
  type SelectOption,
  type SelectOptionGroup,
} from "../ui/Select";

/**
 * Example usage of the Select components
 *
 * This file demonstrates:
 * - Basic Radix UI Select usage
 * - SimpleSelect for easy option arrays
 * - GroupedSelect for organized options
 * - Form integration and validation
 */

const SelectExamples: React.FC = () => {
  const [basicValue, setBasicValue] = useState<string>("");
  const [simpleValue, setSimpleValue] = useState<string>("");
  const [groupedValue, setGroupedValue] = useState<string>("");
  const [statusValue, setStatusValue] = useState<string>("");

  // Simple options array
  const statusOptions: SelectOption[] = [
    {label: "Approved", value: "approved"},
    {label: "Pending", value: "pending"},
    {label: "Rejected", value: "rejected"},
    {label: "Draft", value: "draft"},
    {label: "Archived", value: "archived", disabled: true},
  ];

  // Grouped options for more complex scenarios
  const productGroups: SelectOptionGroup[] = [
    {
      label: "Electronics",
      options: [
        {label: "Smartphone", value: "smartphone"},
        {label: "Laptop", value: "laptop"},
        {label: "Tablet", value: "tablet"},
      ],
    },
    {
      label: "Clothing",
      options: [
        {label: "T-Shirt", value: "tshirt"},
        {label: "Jeans", value: "jeans"},
        {label: "Sneakers", value: "sneakers"},
      ],
    },
    {
      label: "Books",
      options: [
        {label: "Fiction", value: "fiction"},
        {label: "Non-Fiction", value: "nonfiction"},
        {label: "Technical", value: "technical"},
      ],
    },
  ];

  return (
    <div className="p-6 space-y-8 max-w-2xl mx-auto">
      <div>
        <h1 className="text-2xl font-bold mb-4">Select Component Examples</h1>
        <p className="text-gray-600 mb-8">
          Various implementations of the Radix UI Select component.
        </p>
      </div>

      {/* Basic Radix UI Select */}
      <div className="space-y-4">
        <h2 className="text-lg font-semibold">1. Basic Radix UI Select</h2>
        <p className="text-sm text-gray-600">
          Using the raw Radix UI components for maximum flexibility.
        </p>

        <Select value={basicValue} onValueChange={setBasicValue}>
          <SelectTrigger className="w-full">
            <SelectValue placeholder="Choose a status..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="approved">Approved</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="rejected">Rejected</SelectItem>
            <SelectItem value="draft">Draft</SelectItem>
            <SelectItem value="archived" disabled>
              Archived (Disabled)
            </SelectItem>
          </SelectContent>
        </Select>

        {basicValue && (
          <p className="text-sm text-gray-600">Selected: {basicValue}</p>
        )}
      </div>

      {/* SimpleSelect */}
      <div className="space-y-4">
        <h2 className="text-lg font-semibold">2. SimpleSelect Component</h2>
        <p className="text-sm text-gray-600">
          Simplified component that accepts an array of options.
        </p>

        <SimpleSelect
          label="Review Status"
          placeholder="Select status..."
          options={statusOptions}
          value={simpleValue}
          onValueChange={setSimpleValue}
          description="Choose the current status of the review"
          required
        />

        {simpleValue && (
          <p className="text-sm text-gray-600">Selected: {simpleValue}</p>
        )}
      </div>

      {/* GroupedSelect */}
      <div className="space-y-4">
        <h2 className="text-lg font-semibold">3. GroupedSelect Component</h2>
        <p className="text-sm text-gray-600">
          Select with grouped options for better organization.
        </p>

        <GroupedSelect
          label="Product Category"
          placeholder="Select a product..."
          groups={productGroups}
          value={groupedValue}
          onValueChange={setGroupedValue}
          description="Products are organized by category"
        />

        {groupedValue && (
          <p className="text-sm text-gray-600">Selected: {groupedValue}</p>
        )}
      </div>

      {/* Error State Example */}
      <div className="space-y-4">
        <h2 className="text-lg font-semibold">4. Error State</h2>
        <p className="text-sm text-gray-600">
          Select with validation error styling.
        </p>

        <SimpleSelect
          label="Status (Required)"
          placeholder="Please select a status..."
          options={statusOptions}
          value={statusValue}
          onValueChange={setStatusValue}
          error={!statusValue ? "Status is required" : undefined}
          required
        />
      </div>

      {/* Form Integration Example */}
      <div className="space-y-4">
        <h2 className="text-lg font-semibold">5. Form Integration</h2>
        <p className="text-sm text-gray-600">
          How to use the Select components in forms.
        </p>

        <form className="space-y-4" onSubmit={(e) => e.preventDefault()}>
          <SimpleSelect
            name="review_status"
            label="Review Status"
            options={statusOptions}
            value={simpleValue}
            onValueChange={setSimpleValue}
            required
          />

          <GroupedSelect
            name="product_category"
            label="Product Category"
            groups={productGroups}
            value={groupedValue}
            onValueChange={setGroupedValue}
            required
          />

          <button
            type="submit"
            className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            disabled={!simpleValue || !groupedValue}
          >
            Submit Form
          </button>
        </form>
      </div>

      {/* Current Values Display */}
      <div className="space-y-4 p-4 bg-gray-50 rounded-md">
        <h3 className="font-semibold">Current Values:</h3>
        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <strong>Basic:</strong> {basicValue || "None"}
          </div>
          <div>
            <strong>Simple:</strong> {simpleValue || "None"}
          </div>
          <div>
            <strong>Grouped:</strong> {groupedValue || "None"}
          </div>
          <div>
            <strong>Status:</strong> {statusValue || "None"}
          </div>
        </div>
      </div>
    </div>
  );
};

export default SelectExamples;
