"use client";

import type {
  Enrollment,
  EnrollmentSort,
} from "@/types/enrollment";

import EnrollmentStatusBadge from "./EnrollmentStatusBadge";

interface EnrollmentTableProps {
  data: Enrollment[];
  loading: boolean;

  sort: EnrollmentSort;
  direction: "asc" | "desc";

  onSort: (column: EnrollmentSort) => void;

  onEdit: (enrollment: Enrollment) => void;
  onDelete: (enrollment: Enrollment) => void;
}

interface SortButtonProps {
  label: string;
  column: EnrollmentSort;
  currentSort: EnrollmentSort;
  direction: "asc" | "desc";
  onSort: (column: EnrollmentSort) => void;
}

function SortButton({
  label,
  column,
  currentSort,
  direction,
  onSort,
}: SortButtonProps) {
  const active = currentSort === column;

  return (
    <button
      type="button"
      onClick={() => onSort(column)}
      className="group inline-flex items-center gap-1 font-semibold text-slate-700 transition hover:text-slate-950"
    >
      <span>{label}</span>

      <span
        className={`text-xs ${
          active
            ? "text-slate-900"
            : "text-slate-300 group-hover:text-slate-500"
        }`}
      >
        {active
          ? direction === "asc"
            ? "↑"
            : "↓"
          : "↕"}
      </span>
    </button>
  );
}

function LoadingRows() {
  return (
    <>
      {Array.from({ length: 8 }).map((_, index) => (
        <tr key={index} className="animate-pulse">
          {Array.from({ length: 8 }).map(
            (_, cellIndex) => (
              <td
                key={cellIndex}
                className="px-4 py-4"
              >
                <div className="h-4 rounded bg-slate-100" />
              </td>
            ),
          )}
        </tr>
      ))}
    </>
  );
}

export default function EnrollmentTable({
  data,
  loading,
  sort,
  direction,
  onSort,
  onEdit,
  onDelete,
}: EnrollmentTableProps) {
  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto">
        <table className="min-w-[1250px] w-full border-collapse">
          <thead className="bg-slate-50">
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide">
              <th className="px-4 py-3">
                <SortButton
                  label="NIM"
                  column="student_nim"
                  currentSort={sort}
                  direction={direction}
                  onSort={onSort}
                />
              </th>

              <th className="px-4 py-3">
                <SortButton
                  label="Student"
                  column="student_name"
                  currentSort={sort}
                  direction={direction}
                  onSort={onSort}
                />
              </th>

              <th className="px-4 py-3">
                <SortButton
                  label="Course"
                  column="course_code"
                  currentSort={sort}
                  direction={direction}
                  onSort={onSort}
                />
              </th>

              <th className="px-4 py-3">
                Course Name
              </th>

              <th className="px-4 py-3">
                <SortButton
                  label="Academic Year"
                  column="academic_year"
                  currentSort={sort}
                  direction={direction}
                  onSort={onSort}
                />
              </th>

              <th className="px-4 py-3">
                <SortButton
                  label="Semester"
                  column="semester"
                  currentSort={sort}
                  direction={direction}
                  onSort={onSort}
                />
              </th>

              <th className="px-4 py-3">
                <SortButton
                  label="Status"
                  column="status"
                  currentSort={sort}
                  direction={direction}
                  onSort={onSort}
                />
              </th>

              <th className="px-4 py-3 text-right">
                Actions
              </th>
            </tr>
          </thead>

          <tbody className="divide-y divide-slate-100">
            {loading ? (
              <LoadingRows />
            ) : data.length === 0 ? (
              <tr>
                <td
                  colSpan={8}
                  className="px-6 py-16 text-center"
                >
                  <div className="mx-auto max-w-sm">
                    <div className="text-sm font-semibold text-slate-800">
                      No enrollments found
                    </div>

                    <p className="mt-1 text-sm text-slate-500">
                      Try changing your search or filter
                      criteria.
                    </p>
                  </div>
                </td>
              </tr>
            ) : (
              data.map((enrollment) => (
                <tr
                  key={enrollment.id}
                  className="transition hover:bg-slate-50"
                >
                  <td className="whitespace-nowrap px-4 py-4 text-sm font-medium text-slate-900">
                    {enrollment.student_nim}
                  </td>

                  <td className="px-4 py-4">
                    <div className="text-sm font-medium text-slate-900">
                      {enrollment.student_name}
                    </div>

                    <div className="mt-0.5 text-xs text-slate-500">
                      {enrollment.student_email}
                    </div>
                  </td>

                  <td className="whitespace-nowrap px-4 py-4">
                    <div className="text-sm font-semibold text-slate-900">
                      {enrollment.course_code}
                    </div>

                    <div className="mt-0.5 text-xs text-slate-500">
                      {enrollment.course_credits} credits
                    </div>
                  </td>

                  <td className="px-4 py-4 text-sm text-slate-700">
                    {enrollment.course_name}
                  </td>

                  <td className="whitespace-nowrap px-4 py-4 text-sm text-slate-700">
                    {enrollment.academic_year}
                  </td>

                  <td className="whitespace-nowrap px-4 py-4 text-sm text-slate-700">
                    {enrollment.semester}
                  </td>

                  <td className="whitespace-nowrap px-4 py-4">
                    <EnrollmentStatusBadge
                      status={enrollment.status}
                    />
                  </td>

                  <td className="whitespace-nowrap px-4 py-4">
                    <div className="flex justify-end gap-2">
                      <button
                        type="button"
                        onClick={() =>
                          onEdit(enrollment)
                        }
                        className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-950"
                      >
                        Edit
                      </button>

                      <button
                        type="button"
                        onClick={() =>
                          onDelete(enrollment)
                        }
                        className="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}