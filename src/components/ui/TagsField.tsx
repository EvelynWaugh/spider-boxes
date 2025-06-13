import React, { useCallback, useState, useEffect } from "react";

import { Cross2Icon } from "@radix-ui/react-icons";

interface TagsFieldProps {
  value: string | string[] | null;
  onChange: (value: string[]) => void;
  placeholder?: string;
}
const TagsField: React.FC<TagsFieldProps> = ({ value, onChange, placeholder = "Enter tags..." }) => {
  const [inputValue, setInputValue] = useState("");
  const [tags, setTags] = useState<string[]>(() => {
    if (!value) return [];
    if (Array.isArray(value)) return value;
    return typeof value === "string"
      ? value
          .split(",")
          .map((tag) => tag.trim())
          .filter(Boolean)
      : [];
  });

  // Update local tags when value prop changes
  useEffect(() => {
    if (!value) {
      setTags([]);
    } else if (Array.isArray(value)) {
      setTags(value);
    } else if (typeof value === "string") {
      setTags(
        value
          .split(",")
          .map((tag) => tag.trim())
          .filter(Boolean),
      );
    }
  }, [value]);

  const addTag = useCallback(
    (tag: string) => {
      const trimmedTag = tag.trim();
      if (trimmedTag && !tags.includes(trimmedTag)) {
        const newTags = [...tags, trimmedTag];
        setTags(newTags);
        onChange(newTags);
      }
      setInputValue("");
    },
    [tags, onChange],
  );

  const removeTag = useCallback(
    (indexToRemove: number) => {
      const newTags = tags.filter((_, index) => index !== indexToRemove);
      setTags(newTags);
      onChange(newTags);
    },
    [tags, onChange],
  );

  const handleKeyDown = useCallback(
    (e: React.KeyboardEvent<HTMLInputElement>) => {
      if (e.key === "Enter" || e.key === ",") {
        e.preventDefault();
        addTag(inputValue);
      } else if (e.key === "Backspace" && !inputValue && tags.length > 0) {
        removeTag(tags.length - 1);
      }
    },
    [inputValue, addTag, removeTag, tags.length],
  );

  const handleInputChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const value = e.target.value;
      // Handle comma-separated values pasted or typed
      if (value.includes(",")) {
        const newTags = value
          .split(",")
          .map((tag) => tag.trim())
          .filter(Boolean);
        newTags.forEach((tag) => addTag(tag));
      } else {
        setInputValue(value);
      }
    },
    [addTag],
  );

  return (
    <div className="w-full">
      <div className="flex flex-wrap gap-2 p-2 border border-gray-300 rounded-md min-h-[42px] focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500">
        {tags.map((tag, index) => (
          <span key={index} className="inline-flex items-center px-2 py-1 text-sm bg-primary-100 text-primary-800 rounded-md">
            {tag}
            <button
              type="button"
              onClick={() => removeTag(index)}
              className="ml-1 text-primary-600 hover:text-primary-800 focus:outline-none"
            >
              <Cross2Icon className="w-3 h-3" />
            </button>
          </span>
        ))}
        <input
          type="text"
          value={inputValue}
          onChange={handleInputChange}
          onKeyDown={handleKeyDown}
          placeholder={tags.length === 0 ? placeholder : ""}
          className="flex-1 min-w-[120px] border-none outline-none bg-transparent"
        />
      </div>
      <p className="mt-1 text-xs text-gray-500">Press Enter or comma to add tags. Backspace to remove the last tag.</p>
    </div>
  );
};

export default TagsField;
