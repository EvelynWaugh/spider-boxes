import React from "react";
import * as DialogPrimitive from "@radix-ui/react-dialog";
import {motion, AnimatePresence} from "framer-motion";
import {Cross1Icon} from "@radix-ui/react-icons";
import {cn} from "../../utils/cn";

interface DialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  children: React.ReactNode;
}

interface DialogContentProps {
  children: React.ReactNode;
  className?: string;
  size?: "sm" | "md" | "lg" | "xl";
}

interface DialogHeaderProps {
  children: React.ReactNode;
  className?: string;
}

interface DialogTitleProps {
  children: React.ReactNode;
  className?: string;
}

interface DialogDescriptionProps {
  children: React.ReactNode;
  className?: string;
}

interface DialogFooterProps {
  children: React.ReactNode;
  className?: string;
}

const DialogOverlay = React.forwardRef<
  React.ElementRef<typeof DialogPrimitive.Overlay>,
  React.ComponentPropsWithoutRef<typeof DialogPrimitive.Overlay>
>(({className, ...props}, ref) => (
  <DialogPrimitive.Overlay
    ref={ref}
    className={cn("dialog-overlay", className)}
    {...props}
  />
));

const DialogContent = React.forwardRef<
  React.ElementRef<typeof DialogPrimitive.Content>,
  DialogContentProps
>(({className, size = "md", children, ...props}, ref) => {
  const sizeClasses = {
    sm: "dialog-content-sm",
    md: "dialog-content-md",
    lg: "dialog-content-lg",
    xl: "dialog-content-xl",
  };

  return (
    <DialogPrimitive.Portal>
      <AnimatePresence>
        <motion.div
          initial={{opacity: 0}}
          animate={{opacity: 1}}
          exit={{opacity: 0}}
          transition={{duration: 0.2}}
        >
          <DialogOverlay />
        </motion.div>
        <DialogPrimitive.Content
          ref={ref}
          className={cn("dialog-content", sizeClasses[size], className)}
          asChild
          {...props}
          onPointerDownOutside={(event) => {
            // Check if the WordPress media library is open.
            // If it is, prevent the Dialog from closing when we click on the media library.
            if (document.body.classList.contains("wp-media-library-open")) {
              event.preventDefault();
            }
          }}
        >
          <motion.div
            initial={{
              opacity: 0,
              scale: 0.95,
              x: "-50%",
              y: "-50%",
            }}
            animate={{
              opacity: 1,
              scale: 1,
              x: "-50%",
              y: "-50%",
            }}
            exit={{
              opacity: 0,
              scale: 0.95,
              x: "-50%",
              y: "-50%",
            }}
            transition={{duration: 0.2}}
            style={{
              position: "fixed",
              left: "50%",
              top: "50%",
            }}
          >
            {children}
            <DialogPrimitive.Close className="dialog-close">
              <Cross1Icon className="dialog-close-icon" />
              <span className="sr-only">Close</span>
            </DialogPrimitive.Close>
          </motion.div>
        </DialogPrimitive.Content>
      </AnimatePresence>
    </DialogPrimitive.Portal>
  );
});

const DialogHeader: React.FC<DialogHeaderProps> = ({className, ...props}) => (
  <div className={cn("dialog-header", className)} {...props} />
);

const DialogTitle = React.forwardRef<
  React.ElementRef<typeof DialogPrimitive.Title>,
  DialogTitleProps
>(({className, ...props}, ref) => (
  <DialogPrimitive.Title
    ref={ref}
    className={cn("dialog-title", className)}
    {...props}
  />
));

const DialogDescription = React.forwardRef<
  React.ElementRef<typeof DialogPrimitive.Description>,
  DialogDescriptionProps
>(({className, ...props}, ref) => (
  <DialogPrimitive.Description
    ref={ref}
    className={cn("dialog-description", className)}
    {...props}
  />
));

const DialogFooter: React.FC<DialogFooterProps> = ({className, ...props}) => (
  <div className={cn("dialog-footer", className)} {...props} />
);

export const Dialog: React.FC<DialogProps> = ({children, ...props}) => (
  <DialogPrimitive.Root {...props}>{children}</DialogPrimitive.Root>
);

Dialog.displayName = "Dialog";
DialogOverlay.displayName = DialogPrimitive.Overlay.displayName;
DialogContent.displayName = DialogPrimitive.Content.displayName;
DialogHeader.displayName = "DialogHeader";
DialogTitle.displayName = DialogPrimitive.Title.displayName;
DialogDescription.displayName = DialogPrimitive.Description.displayName;
DialogFooter.displayName = "DialogFooter";

export {
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
};
