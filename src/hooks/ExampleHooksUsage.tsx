import React, {Fragment, useEffect, useState} from "react";
import {addFilter, addAction, applyFilters, doAction} from "./createHooks";

/**
 * Example component demonstrating WordPress hooks usage in React
 */
const ExampleHooksUsage: React.FC = () => {
  const [content, setContent] = useState<string>("");
  const [actionCount, setActionCount] = useState<number>(0);

  useEffect(() => {
    // Add example filters
    addFilter(
      "spider_boxes_content",
      "example/add_prefix",
      (content: string) => {
        return `[Spider Boxes] ${content}`;
      },
      10
    );

    addFilter(
      "spider_boxes_content",
      "example/add_suffix",
      (content: string) => {
        return `${content} [Modified by Filter]`;
      },
      20
    );

    // Add example actions
    addAction("spider_boxes_component_mount", "example/log_mount", () => {
      console.log("ExampleHooksUsage component mounted");
      setActionCount((prev) => prev + 1);
    });

    addAction(
      "spider_boxes_component_mount",
      "example/secondary_action",
      () => {
        console.log("Secondary action triggered on mount");
        setActionCount((prev) => prev + 1);
      }
    );

    // Trigger action on component mount
    doAction("spider_boxes_component_mount");

    // Apply filters to content
    const filteredContent = applyFilters("spider_boxes_content", "Hello World");
    setContent(filteredContent);

    // Cleanup - remove hooks when component unmounts
    return () => {
      // Note: In a real application, you might want to store references to callbacks
      // to properly remove them on unmount
    };
  }, []);

  const handleButtonClick = () => {
    doAction("spider_boxes_button_clicked", "Button was clicked!");
  };

  const handleContentChange = (newContent: string) => {
    const filteredContent = applyFilters("spider_boxes_content", newContent);
    setContent(filteredContent);
  };

  return (
    <Fragment>
      {applyFilters("spider_boxes_before_example_content", null)}

      <div className="example-hooks-usage">
        <h3>WordPress Hooks Example</h3>

        <div className="content-section">
          <h4>Filtered Content:</h4>
          <p>{content}</p>
        </div>

        <div className="action-section">
          <h4>Actions Triggered: {actionCount}</h4>
          <button
            onClick={handleButtonClick}
            className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
          >
            Trigger Action
          </button>
        </div>

        <div className="demo-section">
          <h4>Test Filter:</h4>
          <input
            type="text"
            placeholder="Enter text to filter"
            onChange={(e) => handleContentChange(e.target.value)}
            className="border border-gray-300 rounded px-3 py-2 mr-2"
          />
        </div>
      </div>

      {applyFilters("spider_boxes_after_example_content", null)}
    </Fragment>
  );
};

export default ExampleHooksUsage;
