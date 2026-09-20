"use client";

import type { EnrollmentStatus, Semester } from "@/types/enrollment";

interface EnrollmentToolbarProps {
  search: string;

  status: EnrollmentStatus | "";

  semester: Semester | "";

  academicYear: string;

  onSearchChange: (value: string) => void;

  onStatusChange: (value: EnrollmentStatus | "") => void;

  onSemesterChange: (value: Semester | "") => void;

  onAcademicYearChange: (value: string) => void;

  onReset: () => void;

  onCreate: () => void;

  onAdvancedQuery: () => void;

  advancedQueryActive?: boolean;
  onExport: () => void;

  exporting: boolean;
}

export default function EnrollmentToolbar({
  search,
  status,
  semester,
  academicYear,
  onSearchChange,
  onStatusChange,
  onSemesterChange,
  onAcademicYearChange,
  onReset,
  onAdvancedQuery,
  advancedQueryActive,
  onCreate,
  onExport,
  exporting,
}: EnrollmentToolbarProps) {
  const hasFilters =
    search !== "" || status !== "" || semester !== "" || academicYear !== "";

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div className="flex flex-col gap-4">
        {/* ==================================================
            SEARCH
        ================================================== */}

        <div className="flex flex-col gap-1">
          <label
            htmlFor="enrollment-search"
            className="text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            Search
          </label>

          <input
            id="enrollment-search"
            type="search"
            value={search}
            onChange={(event) => onSearchChange(event.target.value)}
            placeholder="Search NIM, student name, or course code..."
            className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
          />
        </div>

        {/* ==================================================
            QUICK FILTERS
        ================================================== */}

        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <div className="flex flex-col gap-1">
            <label
              htmlFor="status"
              className="text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Status
            </label>

            <select
              id="status"
              value={status}
              onChange={(event) =>
                onStatusChange(event.target.value as EnrollmentStatus | "")
              }
              className="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
            >
              <option value="">All statuses</option>

              <option value="DRAFT">Draft</option>

              <option value="SUBMITTED">Submitted</option>

              <option value="APPROVED">Approved</option>

              <option value="REJECTED">Rejected</option>
            </select>
          </div>

          <div className="flex flex-col gap-1">
            <label
              htmlFor="semester"
              className="text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Semester
            </label>

            <select
              id="semester"
              value={semester}
              onChange={(event) =>
                onSemesterChange(event.target.value as Semester | "")
              }
              className="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
            >
              <option value="">All semesters</option>

              <option value="GANJIL">Ganjil</option>

              <option value="GENAP">Genap</option>
            </select>
          </div>

          <div className="flex flex-col gap-1">
            <label
              htmlFor="academic-year"
              className="text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Academic Year
            </label>

            <input
              id="academic-year"
              type="text"
              value={academicYear}
              onChange={(event) => onAcademicYearChange(event.target.value)}
              placeholder="2025/2026"
              maxLength={9}
              className="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
            />
          </div>
        </div>

        {/* ==================================================
            ACTIONS
        ================================================== */}

        <div className="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex flex-wrap items-center gap-2">
            <button
              type="button"
              onClick={onExport}
              disabled={exporting}
              className="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {exporting ? "Exporting..." : "Export CSV"}
            </button>

            <button
              type="button"
              onClick={onCreate}
              className="h-10 rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"
            >
              + Tambah
            </button>
            {hasFilters && (
              <button
                type="button"
                onClick={onReset}
                className="h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
              >
                Reset filters
              </button>
            )}

            <button
              type="button"
              onClick={onAdvancedQuery}
              className={`h-10 rounded-lg border px-4 text-sm font-semibold transition ${
                advancedQueryActive
                  ? "border-slate-950 bg-slate-950 text-white hover:bg-slate-800"
                  : "border-slate-300 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50"
              }`}
            >
              Advanced Query
              {advancedQueryActive && (
                <span className="ml-2 rounded-full bg-white/15 px-1.5 py-0.5 text-xs">
                  Active
                </span>
              )}
            </button>
          </div>

          <p className="text-xs text-slate-400">
            Advanced filters and ordering are executed server-side.
          </p>
        </div>
      </div>
    </div>
  );
}
