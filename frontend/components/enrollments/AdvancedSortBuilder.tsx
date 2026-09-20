"use client";

import type {
  AdvancedSort,
  EnrollmentSort,
} from "@/types/enrollment";

interface AdvancedSortBuilderProps {
  sorts: AdvancedSort[];

  onChange: (
    sorts: AdvancedSort[],
  ) => void;

  onApply: () => void;

  onClear: () => void;

  loading?: boolean;
}

const SORT_FIELDS: Array<{
  value: EnrollmentSort;
  label: string;
}> = [
  {
    value: "id",
    label: "Enrollment ID",
  },
  {
    value: "student_nim",
    label: "Student NIM",
  },
  {
    value: "student_name",
    label: "Student Name",
  },
  {
    value: "student_email",
    label: "Student Email",
  },
  {
    value: "course_code",
    label: "Course Code",
  },
  {
    value: "course_name",
    label: "Course Name",
  },
  {
    value: "course_credits",
    label: "Course Credits",
  },
  {
    value: "academic_year",
    label: "Academic Year",
  },
  {
    value: "semester",
    label: "Semester",
  },
  {
    value: "status",
    label: "Status",
  },
  {
    value: "created_at",
    label: "Created At",
  },
];

function createId(): string {
  return `${Date.now()}-${Math.random()
    .toString(36)
    .slice(2)}`;
}

function createSort(): AdvancedSort {
  return {
    id: createId(),
    field: "academic_year",
    direction: "desc",
  };
}

export default function AdvancedSortBuilder({
  sorts,
  onChange,
  onApply,
  onClear,
  loading = false,
}: AdvancedSortBuilderProps) {
  function addSort() {
    if (sorts.length >= 5) {
      return;
    }

    const usedFields = new Set(
      sorts.map((sort) => sort.field),
    );

    const availableField =
      SORT_FIELDS.find(
        (field) =>
          !usedFields.has(field.value),
      );

    if (!availableField) {
      return;
    }

    onChange([
      ...sorts,
      {
        ...createSort(),
        field: availableField.value,
      },
    ]);
  }

  function removeSort(id: string) {
    onChange(
      sorts.filter(
        (sort) => sort.id !== id,
      ),
    );
  }

  function updateSort(
    id: string,
    patch: Partial<AdvancedSort>,
  ) {
    onChange(
      sorts.map((sort) =>
        sort.id === id
          ? {
              ...sort,
              ...patch,
            }
          : sort,
      ),
    );
  }

  return (
    <section className="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="border-b border-slate-200 px-4 py-4">
        <div>
          <h2 className="text-sm font-semibold text-slate-900">
            Advanced Ordering
          </h2>

          <p className="mt-1 text-xs text-slate-500">
            Sort by multiple columns in
            priority order.
          </p>
        </div>
      </div>

      <div className="space-y-3 p-4">
        {sorts.length === 0 ? (
          <div className="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center">
            <p className="text-sm font-medium text-slate-700">
              No advanced ordering
            </p>

            <p className="mt-1 text-xs text-slate-500">
              The normal table header sorting
              is currently active.
            </p>
          </div>
        ) : (
          sorts.map((sort, index) => (
            <div
              key={sort.id}
              className="rounded-lg border border-slate-200 bg-slate-50 p-3"
            >
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-[auto_1fr_180px_auto] sm:items-end">
                <div className="pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                  #{index + 1}
                </div>

                <div>
                  <label className="mb-1 block text-xs font-medium text-slate-500">
                    Column
                  </label>

                  <select
                    value={sort.field}
                    onChange={(event) =>
                      updateSort(
                        sort.id,
                        {
                          field:
                            event.target
                              .value as EnrollmentSort,
                        },
                      )
                    }
                    className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                  >
                    {SORT_FIELDS.map(
                      (field) => {
                        const usedByOther =
                          sorts.some(
                            (item) =>
                              item.id !==
                                sort.id &&
                              item.field ===
                                field.value,
                          );

                        return (
                          <option
                            key={
                              field.value
                            }
                            value={
                              field.value
                            }
                            disabled={
                              usedByOther
                            }
                          >
                            {field.label}
                          </option>
                        );
                      },
                    )}
                  </select>
                </div>

                <div>
                  <label className="mb-1 block text-xs font-medium text-slate-500">
                    Direction
                  </label>

                  <select
                    value={sort.direction}
                    onChange={(event) =>
                      updateSort(
                        sort.id,
                        {
                          direction:
                            event.target
                              .value as
                              | "asc"
                              | "desc",
                        },
                      )
                    }
                    className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                  >
                    <option value="asc">
                      Ascending
                    </option>
                    <option value="desc">
                      Descending
                    </option>
                  </select>
                </div>

                <button
                  type="button"
                  onClick={() =>
                    removeSort(sort.id)
                  }
                  className="h-10 px-3 text-sm font-medium text-red-600 hover:text-red-800"
                >
                  Remove
                </button>
              </div>
            </div>
          ))
        )}

        <div className="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
          <button
            type="button"
            disabled={sorts.length >= 5}
            onClick={addSort}
            className="h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
          >
            + Add sort level
          </button>

          <div className="flex items-center gap-3">
            <span className="text-xs text-slate-500">
              {sorts.length}/5 levels
            </span>

            <button
              type="button"
              disabled={sorts.length === 0}
              onClick={onClear}
              className="h-10 px-3 text-sm font-medium text-slate-600 hover:text-slate-950 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Clear
            </button>

            <button
              type="button"
              onClick={onApply}
              disabled={loading}
              className="h-10 rounded-lg bg-slate-900 px-5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {loading
                ? "Applying..."
                : "Apply ordering"}
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}