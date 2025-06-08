import React, {useCallback, useState, useEffect} from "react";
import {useForm, FormApi} from "@tanstack/react-form";

import {
  StarIcon,
  ImageIcon,
  Cross2Icon,
  VideoIcon,
  PlayIcon,
  SpeakerLoudIcon,
} from "@radix-ui/react-icons";

// WordPress Media Library types
declare global {
  interface Window {
    wp: {
      media: {
        (options?: any): {
          on: (event: string, callback: Function) => void;
          off: (event: string, callback?: Function) => void;
          open: () => void;
          state: () => {
            get: (key: string) => {
              toJSON: () => any[];
            };
          };
        };
        attachment: (id: string) => {
          fetch: () => Promise<any>;
          get: (key: string) => any;
        };
      };
    };
  }
}

interface MediaData {
  id: string;
  url: string;
  filename: string;
  alt?: string;
  caption?: string;
  title?: string;
  type: string;
}

interface MediaFieldProps {
  value: string[] | string | null;
  onChange: (value: string[] | string | null) => void;
  multiple?: boolean;
  mediaType?: string;
}

const MediaField: React.FC<MediaFieldProps> = ({
  value,
  onChange,
  multiple = false,
  mediaType = "image",
}) => {
  const [mediaData, setMediaData] = useState<Record<string, MediaData>>({});
  const [loading, setLoading] = useState<Record<string, boolean>>({});
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const [isPreviewOpen, setIsPreviewOpen] = useState(false);

  // Helper function to determine if a file is an image
  const isImageFile = (data: MediaData): boolean => {
    return (
      data.type === "image" ||
      data.url.match(/\.(jpg|jpeg|png|gif|webp|svg|bmp|tiff|ico)$/i) !== null
    );
  }; // Helper function to determine if a file is a video
  const isVideoFile = (data: MediaData): boolean => {
    return (
      data.type === "video" ||
      data.url.match(/\.(mp4|webm|ogg|avi|mov|wmv|flv|mkv|m4v|3gp|ogv)$/i) !==
        null
    );
  };

  // console.log(mediaType, value, mediaData, loading);

  // Helper function to determine if a file is an audio
  const isAudioFile = (data: MediaData): boolean => {
    return (
      data.type === "audio" ||
      data.url.match(/\.(mp3|wav|ogg|aac|flac|wma|m4a|opus)$/i) !== null
    );
  };
  // Helper function to get the appropriate icon for media type
  const getMediaIcon = (data?: MediaData): React.ReactNode => {
    if (data && isVideoFile(data)) {
      return <VideoIcon className="w-6 h-6 text-gray-400" />;
    }
    if (data && isAudioFile(data)) {
      return <SpeakerLoudIcon className="w-6 h-6 text-gray-400" />;
    }
    return <ImageIcon className="w-6 h-6 text-gray-400" />;
  }; // Helper function to get file type display name
  const getFileTypeDisplay = (data: MediaData): string => {
    if (isVideoFile(data)) return "Video file";
    if (isAudioFile(data)) return "Audio file";
    if (isImageFile(data)) return "Image file";
    return "Media file";
  };

  // Handle preview opening
  const handlePreview = useCallback((url: string) => {
    setPreviewUrl(url);
    setIsPreviewOpen(true);
  }, []);

  // Handle preview closing
  const handleClosePreview = useCallback(() => {
    setPreviewUrl(null);
    setIsPreviewOpen(false);
  }, []);

  // Fetch media data for given IDs
  const fetchMediaData = useCallback(
    async (ids: string[]) => {
      if (!window.wp?.media) return;

      const newMediaData: Record<string, MediaData> = {};
      const loadingUpdates: Record<string, boolean> = {};

      // Filter out IDs that are already loaded or loading
      const idsToFetch = ids.filter((id) => !mediaData[id] && !loading[id]);

      if (idsToFetch.length === 0) return;

      // Set loading state for new IDs
      idsToFetch.forEach((id) => {
        loadingUpdates[id] = true;
      });

      setLoading((prev) => ({...prev, ...loadingUpdates}));

      for (const id of idsToFetch) {
        try {
          const attachment = window.wp.media.attachment(id);
          await attachment.fetch();

          const data = {
            id,
            url: attachment.get("url") || "",
            filename: attachment.get("filename") || "",
            alt: attachment.get("alt") || "",
            caption: attachment.get("caption") || "",
            title: attachment.get("title") || "",
            type: attachment.get("type") || mediaType,
          };

          newMediaData[id] = data;
        } catch (error) {
          console.error(`Failed to fetch media data for ID ${id}:`, error);
          // Fallback data for failed requests
          newMediaData[id] = {
            id,
            url: "",
            filename: `Media ${id}`,
            title: `Media ${id}`,
            type: mediaType,
          };
        }
      }

      // Update both media data and loading state
      setMediaData((prev) => ({...prev, ...newMediaData}));
      setLoading((prev) => {
        const updated = {...prev};
        idsToFetch.forEach((id) => {
          updated[id] = false;
        });
        return updated;
      });
    },
    [mediaType] // Remove mediaData and loading from dependencies
  );

  // Effect to fetch media data when value changes
  useEffect(() => {
    const ids: string[] = [];

    if (value) {
      if (Array.isArray(value)) {
        ids.push(...value);
      } else {
        ids.push(value);
      }
    }

    if (ids.length > 0) {
      fetchMediaData(ids);
    }
  }, [value, fetchMediaData]);

  const openMediaLibrary = useCallback(() => {
    if (!window.wp?.media) {
      console.error("WordPress media library is not available");
      return;
    }
    const mediaFrame = window.wp.media({
      title: multiple
        ? `Select ${mediaType === "video" ? "Video" : mediaType === "audio" ? "Audio" : "Media"} Files`
        : `Select ${mediaType === "video" ? "Video" : mediaType === "audio" ? "Audio" : "Media"} File`,
      button: {
        text: `Use Selected ${mediaType === "video" ? "Video" : mediaType === "audio" ? "Audio" : "Media"}`,
      },
      multiple: multiple,
      library: {
        type: mediaType,
      },
    });

    // Add class to body when media library opens
    mediaFrame.on("open", () => {
      document.body.classList.add("wp-media-library-open");
    });

    mediaFrame.on("select", () => {
      const selection = mediaFrame.state().get("selection").toJSON();

      if (multiple) {
        const mediaIds: string[] = selection.map((item: any) =>
          item.id.toString()
        );
        onChange(mediaIds);
      } else {
        const item = selection[0];
        if (item) {
          onChange(item.id.toString());
        }
      }
    });

    // Remove class from body when media library closes
    mediaFrame.on("close", () => {
      document.body.classList.remove("wp-media-library-open");
    });

    mediaFrame.open();
  }, [multiple, mediaType, onChange]);

  const removeMedia = useCallback(
    (indexToRemove?: number) => {
      if (
        multiple &&
        Array.isArray(value) &&
        typeof indexToRemove === "number"
      ) {
        const newValue = value.filter((_, index) => index !== indexToRemove);
        onChange(newValue.length > 0 ? newValue : null);
      } else {
        onChange(null);
      }
    },
    [multiple, value, onChange]
  );

  const renderMediaPreview = (id: string, index?: number) => {
    const data = mediaData[id];
    const isLoading = loading[id];

    if (isLoading) {
      return (
        <div
          key={id}
          className="relative border border-gray-300 rounded-lg p-2 bg-gray-50"
        >
          <div className="flex items-center space-x-3">
            {" "}
            <div className="w-16 h-16 bg-gray-200 rounded flex items-center justify-center animate-pulse">
              {getMediaIcon()}
            </div>
            <div className="flex-1 min-w-0">
              <div className="h-4 bg-gray-200 rounded animate-pulse mb-2"></div>
              <div className="h-3 bg-gray-200 rounded animate-pulse w-2/3"></div>
            </div>
          </div>
        </div>
      );
    }

    if (!data) {
      return (
        <div
          key={id}
          className="relative border border-red-300 rounded-lg p-2 bg-red-50"
        >
          <div className="flex items-center space-x-3">
            <div className="w-16 h-16 bg-red-200 rounded flex items-center justify-center">
              <Cross2Icon className="w-6 h-6 text-red-400" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-red-900">
                Media not found
              </p>
              <p className="text-sm text-red-600">ID: {id}</p>
            </div>
            <button
              type="button"
              onClick={() => removeMedia(index)}
              className="p-1 text-red-500 hover:text-red-700"
              title="Remove media"
            >
              <Cross2Icon className="w-4 h-4" />
            </button>
          </div>
        </div>
      );
    }
    const isImage = isImageFile(data);
    const isVideo = isVideoFile(data);
    const isAudio = isAudioFile(data);

    return (
      <div
        key={id}
        className="relative group border border-gray-300 rounded-lg p-2 bg-gray-50"
      >
        <div className="flex items-center space-x-3">
          {isImage && data.url ? (
            <img
              src={data.url}
              alt={data.alt || data.filename}
              className="w-16 h-16 object-cover rounded"
            />
          ) : isVideo && data.url ? (
            <div
              className="relative w-16 h-16 rounded overflow-hidden group cursor-pointer"
              onClick={() => handlePreview(data.url)}
              title="Click to preview video"
            >
              <video
                src={data.url}
                className="w-full h-full object-cover"
                preload="metadata"
                muted
                poster={data.url + "#t=0.5"}
              />
              <div className="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center transition-opacity group-hover:bg-opacity-50">
                <div className="w-6 h-6 bg-white bg-opacity-90 rounded-full flex items-center justify-center">
                  <PlayIcon className="w-3 h-3 text-gray-800 ml-0.5" />
                </div>{" "}
              </div>
            </div>
          ) : isAudio && data.url ? (
            <div
              className="relative w-16 h-16 rounded overflow-hidden group cursor-pointer bg-blue-50"
              onClick={() => handlePreview(data.url)}
              title="Click to preview audio"
            >
              <div className="w-full h-full flex items-center justify-center">
                <SpeakerLoudIcon className="w-8 h-8 text-blue-600" />
              </div>
              <div className="absolute inset-0 bg-blue-600 bg-opacity-20 flex items-center justify-center transition-opacity group-hover:bg-opacity-30">
                <div className="w-6 h-6 bg-white bg-opacity-90 rounded-full flex items-center justify-center">
                  <PlayIcon className="w-3 h-3 text-gray-800 ml-0.5" />
                </div>
              </div>
            </div>
          ) : (
            <div className="w-16 h-16 bg-gray-200 rounded flex items-center justify-center">
              {getMediaIcon(data)}
            </div>
          )}
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium text-gray-900 truncate">
              {data.title || data.filename}
            </p>{" "}
            <p className="text-sm text-gray-500 truncate">{data.filename}</p>{" "}
            {(isVideo || isImage || isAudio) && (
              <p className="text-xs text-blue-600">
                {getFileTypeDisplay(data)}
              </p>
            )}
          </div>
          <button
            type="button"
            onClick={() => removeMedia(index)}
            className="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-red-500 hover:text-red-700"
            title="Remove media"
          >
            <Cross2Icon className="w-4 h-4" />
          </button>
        </div>
      </div>
    );
  };

  const getDisplayIds = (): string[] => {
    if (!value) return [];
    return Array.isArray(value) ? value : [value];
  };

  const displayIds = getDisplayIds();

  return (
    <div className="space-y-3">
      {displayIds.length > 0 && (
        <div className="space-y-2">
          {displayIds.map((id, index) => renderMediaPreview(id, index))}
        </div>
      )}{" "}
      <button
        type="button"
        onClick={openMediaLibrary}
        className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
      >
        {" "}
        <div className="mr-2">
          {mediaType === "video" ? (
            <VideoIcon className="w-4 h-4" />
          ) : mediaType === "audio" ? (
            <SpeakerLoudIcon className="w-4 h-4" />
          ) : (
            <ImageIcon className="w-4 h-4" />
          )}
        </div>
        {value
          ? multiple
            ? `Add More ${mediaType === "video" ? "Videos" : mediaType === "audio" ? "Audio Files" : "Media"}`
            : `Change ${mediaType === "video" ? "Video" : mediaType === "audio" ? "Audio" : "Media"}`
          : multiple
            ? `Select ${mediaType === "video" ? "Video Files" : mediaType === "audio" ? "Audio Files" : "Media Files"}`
            : `Select ${mediaType === "video" ? "Video File" : mediaType === "audio" ? "Audio File" : "Media File"}`}
      </button>{" "}
      {multiple && Array.isArray(value) && value.length > 0 && (
        <p className="text-sm text-gray-500">
          {value.length} file{value.length !== 1 ? "s" : ""} selected
        </p>
      )}{" "}
      {/* Media Preview Modal */}
      {isPreviewOpen && previewUrl && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75">
          <div className="relative max-w-4xl max-h-full p-4">
            <button
              onClick={handleClosePreview}
              className="absolute -top-2 -right-2 z-10 w-8 h-8 bg-white rounded-full flex items-center justify-center text-gray-700 hover:text-gray-900"
              title="Close preview"
            >
              <Cross2Icon className="w-4 h-4" />
            </button>
            {previewUrl.match(
              /\.(mp4|webm|ogg|avi|mov|wmv|flv|mkv|m4v|3gp|ogv)$/i
            ) ? (
              <video
                src={previewUrl}
                controls
                autoPlay
                className="max-w-full max-h-full rounded-lg"
              />
            ) : (
              <audio
                src={previewUrl}
                controls
                autoPlay
                className="w-full max-w-md rounded-lg bg-white p-4"
              />
            )}
          </div>
        </div>
      )}
    </div>
  );
};

interface FieldOption {
  label: string;
  value?: string;
}

export interface DynamicField {
  id: string;
  type: string;
  title: string;
  description?: string;
  value: any;
  required?: boolean;
  options?: Record<string, FieldOption>;
  min?: number;
  max?: number;
  step?: number;
  rows?: number;
  placeholder?: string;
  multiple?: boolean;
  media_type?: string;
  meta_field?: boolean;
}

interface DynamicFieldRendererProps {
  field: DynamicField;
  value: any;
  onChange: (fieldId: string, isMeta: boolean | undefined, value: any) => void;
  formApi?: FormApi<any, any>;
  validationRules?: {
    required?: boolean;
    minLength?: number;
    maxLength?: number;
    min?: number;
    max?: number;
    pattern?: RegExp;
    custom?: (value: any) => string | null;
  };
}

export const DynamicFieldRenderer: React.FC<DynamicFieldRendererProps> = ({
  field,
  value,
  onChange,
  formApi,
  validationRules,
}) => {
  // Create a form instance for this field
  //   const form = useForm({
  //     defaultValues: {
  //       [field.id]: value || getDefaultValue(field),
  //     },
  //     onSubmit: async ({value: formValue}) => {
  //       // This won't typically be called since we're handling changes immediately
  //       onChange(field.id, field.meta_field, formValue[field.id]);
  //     },
  //   });
  const handleChange = (newValue: any) => {
    onChange(field.id, field.meta_field, newValue);
  };

  // Helper function to get default value based on field type
  function getDefaultValue(field: DynamicField) {
    switch (field.type) {
      case "checkbox":
        return field.options ? [] : false;
      case "range":
        return field.min || 0;
      case "select":
        return "";
      case "media":
        return field.multiple ? [] : null;
      default:
        return "";
    }
  }

  // Update form when external value changes
  //   useEffect(() => {
  //     form.setFieldValue(field.id, value || getDefaultValue(field));
  //   }, [value, field.id, field.type]);

  // Validation function
  const validateField = useCallback(
    (fieldValue: any) => {
      const errors: string[] = [];

      // Built-in required validation
      if (
        field.required &&
        (!fieldValue ||
          fieldValue === "" ||
          (Array.isArray(fieldValue) && fieldValue.length === 0))
      ) {
        errors.push(`${field.title} is required`);
      }

      // Custom validation rules
      if (validationRules) {
        if (
          validationRules.minLength &&
          typeof fieldValue === "string" &&
          fieldValue.length < validationRules.minLength
        ) {
          errors.push(
            `${field.title} must be at least ${validationRules.minLength} characters`
          );
        }

        if (
          validationRules.maxLength &&
          typeof fieldValue === "string" &&
          fieldValue.length > validationRules.maxLength
        ) {
          errors.push(
            `${field.title} must not exceed ${validationRules.maxLength} characters`
          );
        }

        if (
          validationRules.min !== undefined &&
          typeof fieldValue === "number" &&
          fieldValue < validationRules.min
        ) {
          errors.push(`${field.title} must be at least ${validationRules.min}`);
        }

        if (
          validationRules.max !== undefined &&
          typeof fieldValue === "number" &&
          fieldValue > validationRules.max
        ) {
          errors.push(`${field.title} must not exceed ${validationRules.max}`);
        }

        if (
          validationRules.pattern &&
          typeof fieldValue === "string" &&
          !validationRules.pattern.test(fieldValue)
        ) {
          errors.push(`${field.title} format is invalid`);
        }

        if (validationRules.custom) {
          const customError = validationRules.custom(fieldValue);
          if (customError) {
            errors.push(customError);
          }
        }
      }

      return errors.length > 0 ? errors[0] : undefined;
    },
    [field, validationRules]
  );

  //   console.log(field, value);

  const renderField = () => {
    // If form instance is provided, use TanStack Form
    if (formApi) {
      return (
        <formApi.Field
          name={field.id}
          validators={{
            onChange: validateField,
            onBlur: validateField,
          }}
        >
          {(fieldApi) => {
            const handleFieldChange = (newValue: any) => {
              fieldApi.handleChange(newValue);
              // Trigger parent onChange for immediate updates
              onChange(field.id, field.meta_field, newValue);
            };

            return renderFieldInput(
              fieldApi.state.value,
              handleFieldChange,
              fieldApi.handleBlur,
              fieldApi.state.meta.errors
            );
          }}
        </formApi.Field>
      );
    }

    // Fallback to controlled components without TanStack Form
    return renderFieldInput(value, (newValue: any) =>
      onChange(field.id, field.meta_field, newValue)
    );
  };

  const renderFieldInput = (
    currentValue: any,
    handleChange: (value: any) => void,
    handleBlur?: () => void,
    errors?: string[]
  ) => {
    switch (field.type) {
      case "text":
        return (
          <div>
            <input
              type="text"
              value={currentValue || ""}
              onChange={(e) => handleChange(e.target.value)}
              onBlur={handleBlur}
              placeholder={field.placeholder}
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
            />
            {errors && errors.length > 0 && (
              <div className="mt-1 text-sm text-red-600">
                {errors.join(", ")}
              </div>
            )}
          </div>
        );

      case "textarea":
        return (
          <div>
            <textarea
              value={currentValue || ""}
              onChange={(e) => handleChange(e.target.value)}
              onBlur={handleBlur}
              rows={field.rows || 4}
              placeholder={field.placeholder}
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
            />
            {errors && errors.length > 0 && (
              <div className="mt-1 text-sm text-red-600">
                {errors.join(", ")}
              </div>
            )}
          </div>
        );

      case "select":
        return (
          <div>
            <select
              value={currentValue || ""}
              onChange={(e) => handleChange(e.target.value)}
              onBlur={handleBlur}
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="">Select an option</option>
              {field.options &&
                Object.entries(field.options).map(([optionValue, option]) => (
                  <option key={optionValue} value={optionValue}>
                    {option.label}
                  </option>
                ))}
            </select>
            {errors && errors.length > 0 && (
              <div className="mt-1 text-sm text-red-600">
                {errors.join(", ")}
              </div>
            )}
          </div>
        );

      case "range":
        return (
          <div>
            <div className="space-y-2">
              <input
                type="range"
                min={field.min || 0}
                max={field.max || 100}
                step={field.step || 1}
                value={currentValue || field.min || 0}
                onChange={(e) => handleChange(parseInt(e.target.value))}
                onBlur={handleBlur}
                className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
              />
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-500">{field.min || 0}</span>
                <div className="flex items-center space-x-1">
                  {field.id === "review_rating" && (
                    <>
                      {[1, 2, 3, 4, 5].map((star) => (
                        <StarIcon
                          key={star}
                          className={`w-4 h-4 ${
                            star <= (currentValue || 0)
                              ? "text-yellow-400 fill-current"
                              : "text-gray-300"
                          }`}
                        />
                      ))}
                      <span className="ml-2 text-sm font-medium">
                        {currentValue || 0}
                      </span>
                    </>
                  )}
                  {field.id !== "review_rating" && (
                    <span className="text-sm font-medium">
                      {currentValue || 0}
                    </span>
                  )}
                </div>
                <span className="text-sm text-gray-500">
                  {field.max || 100}
                </span>
              </div>
            </div>
            {errors && errors.length > 0 && (
              <div className="mt-1 text-sm text-red-600">
                {errors.join(", ")}
              </div>
            )}
          </div>
        );
      case "datetime":
        return (
          <input
            type="datetime-local"
            value={value ? new Date(value).toISOString().slice(0, 16) : ""}
            onChange={(e) => handleChange(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          />
        );

      case "checkbox":
        if (field.options) {
          // Multiple checkboxes
          const selectedValues = Array.isArray(currentValue)
            ? currentValue
            : [];
          return (
            <div>
              <div className="space-y-2">
                {Object.entries(field.options).map(([optionValue, option]) => (
                  <label key={optionValue} className="flex items-center">
                    <input
                      type="checkbox"
                      checked={selectedValues.includes(optionValue)}
                      onChange={(e) => {
                        const newValues = e.target.checked
                          ? [...selectedValues, optionValue]
                          : selectedValues.filter((v) => v !== optionValue);
                        handleChange(newValues);
                      }}
                      onBlur={handleBlur}
                      className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                    />
                    <span className="ml-2">{option.label}</span>
                  </label>
                ))}
              </div>
              {errors && errors.length > 0 && (
                <div className="mt-1 text-sm text-red-600">
                  {errors.join(", ")}
                </div>
              )}
            </div>
          );
        } else {
          // Single checkbox
          return (
            <div>
              <label className="flex items-center">
                <input
                  type="checkbox"
                  checked={!!currentValue}
                  onChange={(e) => handleChange(e.target.checked)}
                  onBlur={handleBlur}
                  className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                />
                <span className="ml-2">{field.title}</span>
              </label>
              {errors && errors.length > 0 && (
                <div className="mt-1 text-sm text-red-600">
                  {errors.join(", ")}
                </div>
              )}
            </div>
          );
        }
      case "radio":
        return (
          <div className="space-y-2">
            {field.options &&
              Object.entries(field.options).map(([optionValue, option]) => (
                <label key={optionValue} className="flex items-center">
                  <input
                    type="radio"
                    name={field.id}
                    value={optionValue}
                    checked={value === optionValue}
                    onChange={(e) => handleChange(e.target.value)}
                    className="border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                  />
                  <span className="ml-2">{option.label}</span>
                </label>
              ))}
          </div>
        );

      case "switcher":
        return (
          <button
            type="button"
            onClick={() => handleChange(!value)}
            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
              value ? "bg-primary-600" : "bg-gray-200"
            }`}
          >
            <span
              className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                value ? "translate-x-6" : "translate-x-1"
              }`}
            />
          </button>
        );

      case "media":
        return (
          <div>
            <MediaField
              value={currentValue}
              onChange={handleChange}
              multiple={field.multiple}
              mediaType={field.media_type}
            />
            {errors && errors.length > 0 && (
              <div className="mt-1 text-sm text-red-600">
                {errors.join(", ")}
              </div>
            )}
          </div>
        );

      default:
        return (
          <div className="text-gray-500 italic">
            Unsupported field type: {field.type}
          </div>
        );
    }
  };
  return (
    <div className="dynamic-field">
      <label className="block text-sm font-medium text-gray-700">
        {field.title}
        {field.required && <span className="required ml-1">*</span>}
      </label>
      {renderField()}
      {field.description && <p className="description">{field.description}</p>}
    </div>
  );
};
