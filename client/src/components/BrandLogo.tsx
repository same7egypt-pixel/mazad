import React from "react";

type BrandLogoProps = {
  className?: string;
};

export function BrandLogo({ className = "" }: BrandLogoProps) {
  return <span dir="ltr" aria-label="Biddfy.ai" className={`inline-flex items-center font-sans text-[1.72rem] font-black tracking-[-0.08em] text-[#12313a] ${className}`}>
    <span>Biddfy</span><span className="text-[#d96d46]">.ai</span><span aria-hidden className="mb-5 ml-1 h-2 w-2 rounded-full bg-[#d96d46]" />
  </span>;
}
