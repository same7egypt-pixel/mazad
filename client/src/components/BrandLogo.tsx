import React from "react";

type BrandLogoProps = {
  className?: string;
};

const biddfyLogoUrl = "/manus-storage/biddfy-ai-logo_43916dae.webp";

export function BrandLogo({ className = "" }: BrandLogoProps) {
  return <img src={biddfyLogoUrl} alt="Biddfy.ai" className={`block h-9 w-auto max-w-40 object-contain ${className}`} />;
}
