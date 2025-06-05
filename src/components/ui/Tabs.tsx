import React, {createContext, useContext, useState} from "react";
import {motion} from "framer-motion";
import {clsx} from "clsx";

interface TabsContextType {
  value: string;
  setValue: (value: string) => void;
}

const TabsContext = createContext<TabsContextType | undefined>(undefined);

function useTabsContext() {
  const context = useContext(TabsContext);
  if (!context) {
    throw new Error("Tabs components must be used within a Tabs provider");
  }
  return context;
}

interface TabsProps {
  defaultValue: string;
  className?: string;
  children: React.ReactNode;
}

export function Tabs({defaultValue, className, children}: TabsProps) {
  const [value, setValue] = useState(defaultValue);

  return (
    <TabsContext.Provider value={{value, setValue}}>
      <div className={clsx("w-full", className)}>{children}</div>
    </TabsContext.Provider>
  );
}

interface TabsListProps {
  className?: string;
  children: React.ReactNode;
}

export function TabsList({className, children}: TabsListProps) {
  return (
    <div
      className={clsx(
        "inline-flex h-10 items-center justify-center rounded-md bg-gray-100 p-1 text-gray-500",
        className
      )}
    >
      {children}
    </div>
  );
}

interface TabsTriggerProps {
  value: string;
  className?: string;
  children: React.ReactNode;
}

export function TabsTrigger({value, className, children}: TabsTriggerProps) {
  const {value: selectedValue, setValue} = useTabsContext();
  const isSelected = selectedValue === value;

  return (
    <button
      type="button"
      onClick={() => setValue(value)}
      className={clsx(
        "inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-white transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50",
        isSelected
          ? "bg-white text-gray-950 shadow-sm"
          : "text-gray-600 hover:bg-gray-200 hover:text-gray-900",
        className
      )}
    >
      {children}
    </button>
  );
}

interface TabsContentProps {
  value: string;
  className?: string;
  children: React.ReactNode;
}

export function TabsContent({value, className, children}: TabsContentProps) {
  const {value: selectedValue} = useTabsContext();

  if (selectedValue !== value) {
    return null;
  }

  return (
    <motion.div
      initial={{opacity: 0, y: 10}}
      animate={{opacity: 1, y: 0}}
      exit={{opacity: 0, y: -10}}
      transition={{duration: 0.2}}
      className={clsx(
        "mt-2 ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2",
        className
      )}
    >
      {children}
    </motion.div>
  );
}
