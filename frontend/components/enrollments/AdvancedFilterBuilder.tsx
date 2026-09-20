"use client";

import { useMemo } from "react";

import type {
  AdvancedFilter,
  AdvancedFilterField,
  AdvancedFilterLogic,
  AdvancedFilterOperator,
} from "@/types/enrollment";

interface AdvancedFilterBuilderProps {
  filters: AdvancedFilter[];
  logic: AdvancedFilterLogic;

  onLogicChange: (
    logic: AdvancedFilterLogic,
  ) => void;

  onChange: (
    filters: AdvancedFilter[],
  ) => void;

  onApply: () => void;

  onClear: () => void;

  loading?: boolean;
}

interface FieldOption {
  value: AdvancedFilterField;
  label: string;
}

const FIELD_OPTIONS: FieldOption[] = [
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
];

const OPERATOR_LABELS: Record<
  AdvancedFilterOperator,
  string
> = {
  contains: "Contains",
  startsWith: "Starts with",
  equal: "Equals",
  in: "In",
  between: "Between",
  gt: "Greater than",
  gte: "Greater than or equal",
  lt: "Less than",
  lte: "Less than or equal",
};

const FIELD_OPERATORS: Record<
  AdvancedFilterField,
  AdvancedFilterOperator[]
> = {
  student_nim: [
    "contains",
    "startsWith",
    "equal",
    "in",
  ],

  student_name: [
    "contains",
    "startsWith",
    "equal",
    "in",
  ],

  student_email: [
    "contains",
    "startsWith",
    "equal",
    "in",
  ],

  course_code: [
    "contains",
    "startsWith",
    "equal",
    "in",
  ],

  course_name: [
    "contains",
    "startsWith",
    "equal",
    "in",
  ],

  course_credits: [
    "equal",
    "in",
    "between",
    "gt",
    "gte",
    "lt",
    "lte",
  ],

  academic_year: [
    "equal",
    "in",
    "between",
  ],

  semester: [
    "equal",
    "in",
  ],

  status: [
    "equal",
    "in",
  ],
};

function createId(): string {
  return `${Date.now()}-${Math.random()
    .toString(36)
    .slice(2)}`;
}

function createFilter(): AdvancedFilter {
  return {
    id: createId(),
    field: "student_name",
    operator: "contains",
    value: "",
  };
}

function defaultValue(
  field: AdvancedFilterField,
  operator: AdvancedFilterOperator,
): string | string[] {
  if (operator === "in") {
    return [];
  }

  if (operator === "between") {
    return ["", ""];
  }

  if (field === "semester") {
    return "GANJIL";
  }

  if (field === "status") {
    return "APPROVED";
  }

  return "";
}

function operatorsFor(
  field: AdvancedFilterField,
) {
  return FIELD_OPERATORS[field];
}

function inputType(
  field: AdvancedFilterField,
): "text" | "number" {
  return field === "course_credits"
    ? "number"
    : "text";
}

export default function AdvancedFilterBuilder({
  filters,
  logic,
  onLogicChange,
  onChange,
  onApply,
  onClear,
  loading = false,
}: AdvancedFilterBuilderProps) {
  const hasFilters = filters.length > 0;

  const canAddMore = filters.length < 20;

  const activeFilterCount = useMemo(
    () =>
      filters.filter((filter) => {
        if (Array.isArray(filter.value)) {
          return filter.value.some(
            (value) => value.trim() !== "",
          );
        }

        return filter.value.trim() !== "";
      }).length,
    [filters],
  );

  function addFilter() {
    if (!canAddMore) {
      return;
    }

    onChange([
      ...filters,
      createFilter(),
    ]);
  }

  function removeFilter(id: string) {
    onChange(
      filters.filter(
        (filter) => filter.id !== id,
      ),
    );
  }

  function updateFilter(
    id: string,
    patch: Partial<AdvancedFilter>,
  ) {
    onChange(
      filters.map((filter) =>
        filter.id === id
          ? {
              ...filter,
              ...patch,
            }
          : filter,
      ),
    );
  }

  function handleFieldChange(
    filter: AdvancedFilter,
    field: AdvancedFilterField,
  ) {
    const allowed =
      operatorsFor(field);

    const operator = allowed.includes(
      filter.operator,
    )
      ? filter.operator
      : allowed[0];

    updateFilter(filter.id, {
      field,
      operator,
      value: defaultValue(
        field,
        operator,
      ),
    });
  }

  function handleOperatorChange(
    filter: AdvancedFilter,
    operator: AdvancedFilterOperator,
  ) {
    updateFilter(filter.id, {
      operator,
      value: defaultValue(
        filter.field,
        operator,
      ),
    });
  }

  function renderValue(
    filter: AdvancedFilter,
  ) {
    const {
      field,
      operator,
      value,
    } = filter;

    if (operator === "in") {
      const values = Array.isArray(value)
        ? value
        : [];

      return (
        <input
          type={
            field === "course_credits"
              ? "text"
              : "text"
          }
          value={values.join(", ")}
          onChange={(event) => {
            const nextValues =
              event.target.value
                .split(",")
                .map((item) =>
                  item.trim(),
                )
                .filter(Boolean);

            updateFilter(filter.id, {
              value: nextValues,
            });
          }}
          placeholder={
            field === "status"
              ? "APPROVED, SUBMITTED"
              : "value1, value2"
          }
          className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
        />
      );
    }

    if (operator === "between") {
      const values =
        Array.isArray(value)
          ? value
          : ["", ""];

      return (
        <div className="grid grid-cols-2 gap-2">
          <input
            type={inputType(field)}
            value={values[0] ?? ""}
            onChange={(event) => {
              updateFilter(filter.id, {
                value: [
                  event.target.value,
                  values[1] ?? "",
                ],
              });
            }}
            placeholder="From"
            className="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
          />

          <input
            type={inputType(field)}
            value={values[1] ?? ""}
            onChange={(event) => {
              updateFilter(filter.id, {
                value: [
                  values[0] ?? "",
                  event.target.value,
                ],
              });
            }}
            placeholder="To"
            className="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
          />
        </div>
      );
    }

    if (
      field === "status" &&
      (operator === "equal")
    ) {
      return (
        <select
          value={
            typeof value === "string"
              ? value
              : ""
          }
          onChange={(event) =>
            updateFilter(filter.id, {
              value:
                event.target.value,
            })
          }
          className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
        >
          <option value="">
            Select status
          </option>
          <option value="DRAFT">
            Draft
          </option>
          <option value="SUBMITTED">
            Submitted
          </option>
          <option value="APPROVED">
            Approved
          </option>
          <option value="REJECTED">
            Rejected
          </option>
        </select>
      );
    }

    if (
      field === "semester" &&
      operator === "equal"
    ) {
      return (
        <select
          value={
            typeof value === "string"
              ? value
              : ""
          }
          onChange={(event) =>
            updateFilter(filter.id, {
              value:
                event.target.value,
            })
          }
          className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
        >
          <option value="">
            Select semester
          </option>
          <option value="GANJIL">
            Ganjil
          </option>
          <option value="GENAP">
            Genap
          </option>
        </select>
      );
    }

    return (
      <input
        type={inputType(field)}
        value={
          typeof value === "string"
            ? value
            : ""
        }
        onChange={(event) =>
          updateFilter(filter.id, {
            value:
              event.target.value,
          })
        }
        placeholder="Value"
        min={
          field === "course_credits"
            ? 1
            : undefined
        }
        max={
          field === "course_credits"
            ? 6
            : undefined
        }
        className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
      />
    );
  }

  return (
    <section className="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="border-b border-slate-200 px-4 py-4">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 className="text-sm font-semibold text-slate-900">
              Advanced Filters
            </h2>

            <p className="mt-1 text-xs text-slate-500">
              Combine multiple conditions
              using AND or OR.
            </p>
          </div>

          <div className="flex items-center gap-2">
            <span className="text-xs font-medium text-slate-500">
              Logic
            </span>

            <select
              value={logic}
              onChange={(event) =>
                onLogicChange(
                  event.target
                    .value as AdvancedFilterLogic,
                )
              }
              className="h-9 rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
            >
              <option value="AND">
                AND
              </option>
              <option value="OR">
                OR
              </option>
            </select>
          </div>
        </div>
      </div>

      <div className="space-y-3 p-4">
        {!hasFilters && (
          <div className="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center">
            <p className="text-sm font-medium text-slate-700">
              No advanced filters
            </p>

            <p className="mt-1 text-xs text-slate-500">
              Add a condition to build a
              multi-column filter.
            </p>
          </div>
        )}

        {filters.map((filter, index) => (
          <div
            key={filter.id}
            className="rounded-lg border border-slate-200 bg-slate-50 p-3"
          >
            <div className="mb-3 flex items-center justify-between">
              <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Condition {index + 1}
              </span>

              <button
                type="button"
                onClick={() =>
                  removeFilter(filter.id)
                }
                className="text-xs font-medium text-red-600 hover:text-red-800"
              >
                Remove
              </button>
            </div>

            <div className="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_1fr_1.5fr]">
              <div>
                <label className="mb-1 block text-xs font-medium text-slate-500">
                  Column
                </label>

                <select
                  value={filter.field}
                  onChange={(event) =>
                    handleFieldChange(
                      filter,
                      event.target
                        .value as AdvancedFilterField,
                    )
                  }
                  className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                >
                  {FIELD_OPTIONS.map(
                    (option) => (
                      <option
                        key={option.value}
                        value={
                          option.value
                        }
                      >
                        {option.label}
                      </option>
                    ),
                  )}
                </select>
              </div>

              <div>
                <label className="mb-1 block text-xs font-medium text-slate-500">
                  Operator
                </label>

                <select
                  value={filter.operator}
                  onChange={(event) =>
                    handleOperatorChange(
                      filter,
                      event.target
                        .value as AdvancedFilterOperator,
                    )
                  }
                  className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                >
                  {operatorsFor(
                    filter.field,
                  ).map((operator) => (
                    <option
                      key={operator}
                      value={operator}
                    >
                      {
                        OPERATOR_LABELS[
                          operator
                        ]
                      }
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="mb-1 block text-xs font-medium text-slate-500">
                  Value
                </label>

                {renderValue(filter)}
              </div>
            </div>
          </div>
        ))}

        <div className="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
          <button
            type="button"
            disabled={!canAddMore}
            onClick={addFilter}
            className="h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
          >
            + Add condition
          </button>

          <div className="flex items-center gap-3">
            <span className="text-xs text-slate-500">
              {activeFilterCount} active
            </span>

            <button
              type="button"
              onClick={onClear}
              disabled={!hasFilters}
              className="h-10 rounded-lg px-4 text-sm font-medium text-slate-600 hover:text-slate-950 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Clear
            </button>

            <button
              type="button"
              onClick={onApply}
              disabled={loading}
              className="h-10 rounded-lg bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {loading
                ? "Applying..."
                : "Apply filters"}
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}