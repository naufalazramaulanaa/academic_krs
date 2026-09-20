import type { EnrollmentStatus } from "@/types/enrollment";

interface EnrollmentStatusBadgeProps {
  status: EnrollmentStatus;
}

const statusConfig: Record<
  EnrollmentStatus,
  {
    label: string;
    className: string;
  }
> = {
  DRAFT: {
    label: "Draft",
    className:
      "bg-slate-100 text-slate-700 ring-slate-200",
  },

  SUBMITTED: {
    label: "Submitted",
    className:
      "bg-blue-50 text-blue-700 ring-blue-200",
  },

  APPROVED: {
    label: "Approved",
    className:
      "bg-emerald-50 text-emerald-700 ring-emerald-200",
  },

  REJECTED: {
    label: "Rejected",
    className:
      "bg-red-50 text-red-700 ring-red-200",
  },
};

export default function EnrollmentStatusBadge({
  status,
}: EnrollmentStatusBadgeProps) {
  const config = statusConfig[status];

  return (
    <span
      className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${config.className}`}
    >
      {config.label}
    </span>
  );
}