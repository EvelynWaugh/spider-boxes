import React, { useCallback, useState, useEffect } from "react";

import { Cross2Icon, ImageIcon, VideoIcon, PlayIcon, SpeakerLoudIcon } from "@radix-ui/react-icons";

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

const MediaField: React.FC<MediaFieldProps> = ({ value, onChange, multiple = false, mediaType = "image" }) => {
  const [mediaData, setMediaData] = useState<Record<string, MediaData>>({});
  const [loading, setLoading] = useState<Record<string, boolean>>({});
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const [isPreviewOpen, setIsPreviewOpen] = useState(false);

  // Helper function to determine if a file is an image
  const isImageFile = (data: MediaData): boolean => {
    return data.type === "image" || data.url.match(/\.(jpg|jpeg|png|gif|webp|svg|bmp|tiff|ico)$/i) !== null;
  }; // Helper function to determine if a file is a video
  const isVideoFile = (data: MediaData): boolean => {
    return data.type === "video" || data.url.match(/\.(mp4|webm|ogg|avi|mov|wmv|flv|mkv|m4v|3gp|ogv)$/i) !== null;
  };

  // console.log(mediaType, value, mediaData, loading);

  // Helper function to determine if a file is an audio
  const isAudioFile = (data: MediaData): boolean => {
    return data.type === "audio" || data.url.match(/\.(mp3|wav|ogg|aac|flac|wma|m4a|opus)$/i) !== null;
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

      setLoading((prev) => ({ ...prev, ...loadingUpdates }));

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
      setMediaData((prev) => ({ ...prev, ...newMediaData }));
      setLoading((prev) => {
        const updated = { ...prev };
        idsToFetch.forEach((id) => {
          updated[id] = false;
        });
        return updated;
      });
    },
    [mediaType], // Remove mediaData and loading from dependencies
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
        const mediaIds: string[] = selection.map((item: any) => item.id.toString());
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
      if (multiple && Array.isArray(value) && typeof indexToRemove === "number") {
        const newValue = value.filter((_, index) => index !== indexToRemove);
        onChange(newValue.length > 0 ? newValue : null);
      } else {
        onChange(null);
      }
    },
    [multiple, value, onChange],
  );

  const renderMediaPreview = (id: string, index?: number) => {
    const data = mediaData[id];
    const isLoading = loading[id];

    if (isLoading) {
      return (
        <div key={id} className="relative border border-gray-300 rounded-lg p-2 bg-gray-50">
          <div className="flex items-center space-x-3">
            {" "}
            <div className="w-16 h-16 bg-gray-200 rounded flex items-center justify-center animate-pulse">{getMediaIcon()}</div>
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
        <div key={id} className="relative border border-red-300 rounded-lg p-2 bg-red-50">
          <div className="flex items-center space-x-3">
            <div className="w-16 h-16 bg-red-200 rounded flex items-center justify-center">
              <Cross2Icon className="w-6 h-6 text-red-400" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-red-900">Media not found</p>
              <p className="text-sm text-red-600">ID: {id}</p>
            </div>
            <button type="button" onClick={() => removeMedia(index)} className="p-1 text-red-500 hover:text-red-700" title="Remove media">
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
      <div key={id} className="relative group border border-gray-300 rounded-lg p-2 bg-gray-50">
        <div className="flex items-center space-x-3">
          {isImage && data.url ? (
            <img src={data.url} alt={data.alt || data.filename} className="w-16 h-16 object-cover rounded" />
          ) : isVideo && data.url ? (
            <div
              className="relative w-16 h-16 rounded overflow-hidden group cursor-pointer"
              onClick={() => handlePreview(data.url)}
              title="Click to preview video"
            >
              <video src={data.url} className="w-full h-full object-cover" preload="metadata" muted poster={data.url + "#t=0.5"} />
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
            <div className="w-16 h-16 bg-gray-200 rounded flex items-center justify-center">{getMediaIcon(data)}</div>
          )}
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium text-gray-900 truncate">{data.title || data.filename}</p>{" "}
            <p className="text-sm text-gray-500 truncate">{data.filename}</p>{" "}
            {(isVideo || isImage || isAudio) && <p className="text-xs text-blue-600">{getFileTypeDisplay(data)}</p>}
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
      {displayIds.length > 0 && <div className="space-y-2">{displayIds.map((id, index) => renderMediaPreview(id, index))}</div>}{" "}
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
            {previewUrl.match(/\.(mp4|webm|ogg|avi|mov|wmv|flv|mkv|m4v|3gp|ogv)$/i) ? (
              <video src={previewUrl} controls autoPlay className="max-w-full max-h-full rounded-lg" />
            ) : (
              <audio src={previewUrl} controls autoPlay className="w-full max-w-md rounded-lg bg-white p-4" />
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default MediaField;
