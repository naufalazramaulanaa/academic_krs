"use client";

import { useEffect, useMemo, useState } from "react";

import type {
  AdvancedFilterField,
  AdvancedFilterGroup,
  AdvancedFilterItem,
  AdvancedFilterLogic,
  AdvancedFilterOperator,
  AdvancedSortItem,
  EnrollmentSort,
} from "@/types/enrollment";

/*
 * ==========================================================
 * FIELD CONFIGURATION
 * ==========================================================
 */

const FILTER_FIELDS: Array<{
  value: AdvancedFilterField;
  label: string;
}> = [
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

const SORT_FIELDS: Array<{
  value: EnrollmentSort;
  label: string;
}> = [
  {
    value: "id",
    label: "ID",
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

/*
 * ==========================================================
 * OPERATORS
 * ==========================================================
 */

const TEXT_OPERATORS: Array<{
  value: AdvancedFilterOperator;
  label: string;
}> = [
  {
    value: "contains",
    label: "Contains",
  },
  {
    value: "startsWith",
    label: "Starts with",
  },
  {
    value: "equal",
    label: "Equals",
  },
  {
    value: "in",
    label: "In",
  },
];

const CREDITS_OPERATORS: Array<{
  value: AdvancedFilterOperator;
  label: string;
}> = [
  {
    value: "equal",
    label: "Equals",
  },
  {
    value: "in",
    label: "In",
  },
  {
    value: "between",
    label: "Between",
  },
  {
    value: "gt",
    label: "Greater than",
  },
  {
    value: "gte",
    label: "Greater than or equal",
  },
  {
    value: "lt",
    label: "Less than",
  },
  {
    value: "lte",
    label: "Less than or equal",
  },
];

const ACADEMIC_YEAR_OPERATORS: Array<{
  value: AdvancedFilterOperator;
  label: string;
}> = [
  {
    value: "equal",
    label: "Equals",
  },
  {
    value: "in",
    label: "In",
  },
  {
    value: "between",
    label: "Between",
  },
];

const ENUM_OPERATORS: Array<{
  value: AdvancedFilterOperator;
  label: string;
}> = [
  {
    value: "equal",
    label: "Equals",
  },
  {
    value: "in",
    label: "In",
  },
];

/*
 * ==========================================================
 * PROPS
 * ==========================================================
 */

interface AdvancedQueryModalProps {
  open: boolean;

  initialFilters: AdvancedFilterGroup | null;

  initialSorts: AdvancedSortItem[];

  onClose: () => void;

  onApply: (
    filters: AdvancedFilterGroup | null,
    sorts: AdvancedSortItem[],
  ) => void;
}

/*
 * ==========================================================
 * HELPERS
 * ==========================================================
 */

function createId(): string {
  return `${Date.now()}-${Math.random()
    .toString(36)
    .slice(2)}`;
}

function createFilterItem(): AdvancedFilterItem {
  return {
    id: createId(),
    field: "student_name",
    operator: "contains",
    value: "",
  };
}

function createSortItem(): AdvancedSortItem {
  return {
    id: createId(),
    field: "student_name",
    direction: "asc",
  };
}

function getOperators(
  field: AdvancedFilterField,
) {
  if (field === "course_credits") {
    return CREDITS_OPERATORS;
  }

  if (field === "academic_year") {
    return ACADEMIC_YEAR_OPERATORS;
  }

  if (
    field === "semester" ||
    field === "status"
  ) {
    return ENUM_OPERATORS;
  }

  return TEXT_OPERATORS;
}

function getDefaultOperator(
  field: AdvancedFilterField,
): AdvancedFilterOperator {
  return getOperators(field)[0].value;
}

function requiresArray(
  operator: AdvancedFilterOperator,
): boolean {
  return (
    operator === "in" ||
    operator === "between"
  );
}

function getValueArray(
  value: string | string[],
): string[] {
  if (Array.isArray(value)) {
    return value;
  }

  return [value];
}

/*
 * ==========================================================
 * COMPONENT
 * ==========================================================
 */

export default function AdvancedQueryModal({
  open,
  initialFilters,
  initialSorts,
  onClose,
  onApply,
}: AdvancedQueryModalProps) {
  const [filterLogic, setFilterLogic] =
    useState<AdvancedFilterLogic>(
      initialFilters?.logic ?? "AND",
    );

  const [filters, setFilters] =
    useState<AdvancedFilterItem[]>(
      initialFilters?.items ?? [],
    );

  const [sorts, setSorts] =
    useState<AdvancedSortItem[]>(
      initialSorts,
    );

  /*
   * Sync state whenever modal is opened
   * or parent state changes.
   */

  useEffect(() => {
    if (!open) {
      return;
    }

    setFilterLogic(
      initialFilters?.logic ?? "AND",
    );

    setFilters(
      initialFilters?.items ?? [],
    );

    setSorts(initialSorts);
  }, [
    open,
    initialFilters,
    initialSorts,
  ]);

  /*
   * ==========================================================
   * ESCAPE KEY
   * ==========================================================
   */

  useEffect(() => {
    if (!open) {
      return;
    }

    function handleKeyDown(
      event: KeyboardEvent,
    ) {
      if (event.key === "Escape") {
        onClose();
      }
    }

    window.addEventListener(
      "keydown",
      handleKeyDown,
    );

    return () => {
      window.removeEventListener(
        "keydown",
        handleKeyDown,
      );
    };
  }, [open, onClose]);

  /*
   * ==========================================================
   * BODY SCROLL LOCK
   * ==========================================================
   */

  useEffect(() => {
    if (!open) {
      return;
    }

    const previousOverflow =
      document.body.style.overflow;

    document.body.style.overflow = "hidden";

    return () => {
      document.body.style.overflow =
        previousOverflow;
    };
  }, [open]);

  /*
   * ==========================================================
   * FILTER HANDLERS
   * ==========================================================
   */

  function addFilter() {
    if (filters.length >= 20) {
      return;
    }

    setFilters((current) => [
      ...current,
      createFilterItem(),
    ]);
  }

  function removeFilter(id: string) {
    setFilters((current) =>
      current.filter(
        (item) => item.id !== id,
      ),
    );
  }

  function clearFilters() {
    setFilters([]);
  }

  function updateFilter(
    id: string,
    patch: Partial<AdvancedFilterItem>,
  ) {
    setFilters((current) =>
      current.map((item) =>
        item.id === id
          ? {
              ...item,
              ...patch,
            }
          : item,
      ),
    );
  }

  function handleFilterFieldChange(
    id: string,
    field: AdvancedFilterField,
  ) {
    updateFilter(id, {
      field,
      operator: getDefaultOperator(
        field,
      ),
      value: "",
    });
  }

  function handleFilterOperatorChange(
    id: string,
    operator: AdvancedFilterOperator,
  ) {
    updateFilter(id, {
      operator,
      value: requiresArray(operator)
        ? [""]
        : "",
    });
  }

  function updateArrayValue(
    id: string,
    index: number,
    value: string,
  ) {
    setFilters((current) =>
      current.map((item) => {
        if (item.id !== id) {
          return item;
        }

        const values = getValueArray(
          item.value,
        );

        values[index] = value;

        return {
          ...item,
          value: values,
        };
      }),
    );
  }

  function addArrayValue(id: string) {
    setFilters((current) =>
      current.map((item) => {
        if (item.id !== id) {
          return item;
        }

        return {
          ...item,
          value: [
            ...getValueArray(
              item.value,
            ),
            "",
          ],
        };
      }),
    );
  }

  function removeArrayValue(
    id: string,
    index: number,
  ) {
    setFilters((current) =>
      current.map((item) => {
        if (item.id !== id) {
          return item;
        }

        const values = getValueArray(
          item.value,
        );

        if (values.length <= 1) {
          return item;
        }

        return {
          ...item,
          value: values.filter(
            (_, valueIndex) =>
              valueIndex !== index,
          ),
        };
      }),
    );
  }

  /*
   * ==========================================================
   * SORT HANDLERS
   * ==========================================================
   */

  function addSort() {
    if (sorts.length >= 5) {
      return;
    }

    setSorts((current) => [
      ...current,
      createSortItem(),
    ]);
  }

  function removeSort(id: string) {
    setSorts((current) =>
      current.filter(
        (item) => item.id !== id,
      ),
    );
  }

  function clearSorts() {
    setSorts([]);
  }

  function updateSort(
    id: string,
    patch: Partial<AdvancedSortItem>,
  ) {
    setSorts((current) =>
      current.map((item) =>
        item.id === id
          ? {
              ...item,
              ...patch,
            }
          : item,
      ),
    );
  }

  /*
   * ==========================================================
   * ACTIVE FILTER COUNT
   * ==========================================================
   */

  const activeFilterCount = useMemo(
    () =>
      filters.filter((filter) => {
        if (
          Array.isArray(filter.value)
        ) {
          return filter.value.some(
            (value) =>
              value.trim() !== "",
          );
        }

        return (
          filter.value.trim() !== ""
        );
      }).length,
    [filters],
  );

  /*
   * ==========================================================
   * APPLY
   * ==========================================================
   */

  function handleApplyFilters() {
    const validFilters =
      filters.filter((filter) => {
        if (
          Array.isArray(filter.value)
        ) {
          return filter.value.some(
            (value) =>
              value.trim() !== "",
          );
        }

        return (
          filter.value.trim() !== ""
        );
      });

    if (validFilters.length === 0) {
      onApply(null, sorts);

      return;
    }

    onApply(
      {
        logic: filterLogic,
        items: validFilters,
      },
      sorts,
    );
  }

  function handleApplyOrdering() {
    onApply(
      filters.length > 0
        ? {
            logic: filterLogic,
            items: filters,
          }
        : null,
      sorts,
    );
  }

  /*
   * ==========================================================
   * CLOSED
   * ==========================================================
   */

  if (!open) {
    return null;
  }

  /*
   * ==========================================================
   * RENDER
   * ==========================================================
   */

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-[2px]"
      onMouseDown={(event) => {
        if (
          event.target === event.currentTarget
        ) {
          onClose();
        }
      }}
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="advanced-query-title"
        className="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
      >
        {/* ==================================================
            MODAL HEADER
        ================================================== */}

        <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
          <div>
            <h2
              id="advanced-query-title"
              className="text-lg font-bold text-slate-950"
            >
              Advanced Query
            </h2>

            <p className="mt-1 text-sm text-slate-500">
              Build advanced filters and
              multi-column ordering.
            </p>
          </div>

          <button
            type="button"
            onClick={onClose}
            aria-label="Close advanced query"
            className="flex h-9 w-9 items-center justify-center rounded-lg text-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
          >
            ×
          </button>
        </div>

        {/* ==================================================
            MODAL BODY
        ================================================== */}

        <div className="overflow-y-auto p-6">
          <div className="space-y-6">

            {/* ==================================================
                ADVANCED FILTERS
            ================================================== */}

            <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
              <div className="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <h3 className="text-sm font-bold text-slate-900">
                    Advanced Filters
                  </h3>

                  <p className="mt-1 text-xs text-slate-500">
                    Combine multiple conditions
                    using AND or OR.
                  </p>
                </div>

                <div className="flex items-center gap-2">
                  <span className="text-xs text-slate-500">
                    Logic
                  </span>

                  <select
                    value={filterLogic}
                    onChange={(event) =>
                      setFilterLogic(
                        event.target
                          .value as AdvancedFilterLogic,
                      )
                    }
                    className="h-9 rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
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

              <div className="p-5">
                {filters.length === 0 ? (
                  <div className="flex min-h-28 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 text-center">
                    <div>
                      <p className="text-sm font-semibold text-slate-700">
                        No advanced filters
                      </p>

                      <p className="mt-1 text-xs text-slate-500">
                        Add a condition to build a
                        multi-column filter.
                      </p>
                    </div>
                  </div>
                ) : (
                  <div className="space-y-3">
                    {filters.map(
                      (
                        filter,
                        index,
                      ) => {
                        const operators =
                          getOperators(
                            filter.field,
                          );

                        return (
                          <div
                            key={filter.id}
                            className="rounded-xl border border-slate-200 bg-slate-50 p-4"
                          >
                            <div className="mb-3 flex items-center justify-between">
                              <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Condition{" "}
                                {index + 1}
                              </span>

                              <button
                                type="button"
                                onClick={() =>
                                  removeFilter(
                                    filter.id,
                                  )
                                }
                                className="text-xs font-medium text-red-600 hover:text-red-700"
                              >
                                Remove
                              </button>
                            </div>

                            <div className="grid grid-cols-1 gap-3 lg:grid-cols-[1.1fr_1fr_1.7fr_auto]">
                              {/* Field */}

                              <select
                                value={
                                  filter.field
                                }
                                onChange={(
                                  event,
                                ) =>
                                  handleFilterFieldChange(
                                    filter.id,
                                    event
                                      .target
                                      .value as AdvancedFilterField,
                                  )
                                }
                                className="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                              >
                                {FILTER_FIELDS.map(
                                  (
                                    field,
                                  ) => (
                                    <option
                                      key={
                                        field.value
                                      }
                                      value={
                                        field.value
                                      }
                                    >
                                      {
                                        field.label
                                      }
                                    </option>
                                  ),
                                )}
                              </select>

                              {/* Operator */}

                              <select
                                value={
                                  filter.operator
                                }
                                onChange={(
                                  event,
                                ) =>
                                  handleFilterOperatorChange(
                                    filter.id,
                                    event
                                      .target
                                      .value as AdvancedFilterOperator,
                                  )
                                }
                                className="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                              >
                                {operators.map(
                                  (
                                    operator,
                                  ) => (
                                    <option
                                      key={
                                        operator.value
                                      }
                                      value={
                                        operator.value
                                      }
                                    >
                                      {
                                        operator.label
                                      }
                                    </option>
                                  ),
                                )}
                              </select>

                              {/* Value */}

                              <div className="space-y-2">
                                {requiresArray(
                                  filter.operator,
                                ) ? (
                                  <>
                                    {getValueArray(
                                      filter.value,
                                    ).map(
                                      (
                                        value,
                                        valueIndex,
                                      ) => (
                                        <div
                                          key={`${filter.id}-${valueIndex}`}
                                          className="flex gap-2"
                                        >
                                          <input
                                            type={
                                              filter.field ===
                                              "course_credits"
                                                ? "number"
                                                : "text"
                                            }
                                            min={
                                              filter.field ===
                                              "course_credits"
                                                ? 1
                                                : undefined
                                            }
                                            max={
                                              filter.field ===
                                              "course_credits"
                                                ? 6
                                                : undefined
                                            }
                                            value={
                                              value
                                            }
                                            onChange={(
                                              event,
                                            ) =>
                                              updateArrayValue(
                                                filter.id,
                                                valueIndex,
                                                event
                                                  .target
                                                  .value,
                                              )
                                            }
                                            placeholder={
                                              filter.operator ===
                                              "between"
                                                ? valueIndex ===
                                                  0
                                                  ? "Minimum"
                                                  : "Maximum"
                                                : "Value"
                                            }
                                            className="h-10 min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                          />

                                          {filter.operator ===
                                            "in" && (
                                            <button
                                              type="button"
                                              onClick={() =>
                                                removeArrayValue(
                                                  filter.id,
                                                  valueIndex,
                                                )
                                              }
                                              disabled={
                                                getValueArray(
                                                  filter.value,
                                                ).length <=
                                                1
                                              }
                                              className="h-10 w-10 rounded-lg border border-slate-300 bg-white text-slate-500 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                              ×
                                            </button>
                                          )}
                                        </div>
                                      ),
                                    )}

                                    {filter.operator ===
                                      "in" && (
                                      <button
                                        type="button"
                                        onClick={() =>
                                          addArrayValue(
                                            filter.id,
                                          )
                                        }
                                        className="text-left text-xs font-medium text-slate-600 hover:text-slate-950"
                                      >
                                        + Add value
                                      </button>
                                    )}
                                  </>
                                ) : filter.field ===
                                  "status" ? (
                                  <select
                                    value={
                                      typeof filter.value ===
                                      "string"
                                        ? filter.value
                                        : ""
                                    }
                                    onChange={(
                                      event,
                                    ) =>
                                      updateFilter(
                                        filter.id,
                                        {
                                          value:
                                            event
                                              .target
                                              .value,
                                        },
                                      )
                                    }
                                    className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
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
                                ) : filter.field ===
                                  "semester" ? (
                                  <select
                                    value={
                                      typeof filter.value ===
                                      "string"
                                        ? filter.value
                                        : ""
                                    }
                                    onChange={(
                                      event,
                                    ) =>
                                      updateFilter(
                                        filter.id,
                                        {
                                          value:
                                            event
                                              .target
                                              .value,
                                        },
                                      )
                                    }
                                    className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
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
                                ) : (
                                  <input
                                    type={
                                      filter.field ===
                                      "course_credits"
                                        ? "number"
                                        : "text"
                                    }
                                    min={
                                      filter.field ===
                                      "course_credits"
                                        ? 1
                                        : undefined
                                    }
                                    max={
                                      filter.field ===
                                      "course_credits"
                                        ? 6
                                        : undefined
                                    }
                                    value={
                                      typeof filter.value ===
                                      "string"
                                        ? filter.value
                                        : ""
                                    }
                                    onChange={(
                                      event,
                                    ) =>
                                      updateFilter(
                                        filter.id,
                                        {
                                          value:
                                            event
                                              .target
                                              .value,
                                        },
                                      )
                                    }
                                    placeholder={
                                      filter.field ===
                                      "academic_year"
                                        ? "2025/2026"
                                        : "Value"
                                    }
                                    className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                  />
                                )}
                              </div>

                              {/* Remove */}

                              <button
                                type="button"
                                onClick={() =>
                                  removeFilter(
                                    filter.id,
                                  )
                                }
                                className="hidden h-10 rounded-lg border border-red-200 bg-white px-3 text-sm font-medium text-red-600 transition hover:bg-red-50 lg:block"
                              >
                                Remove
                              </button>
                            </div>
                          </div>
                        );
                      },
                    )}
                  </div>
                )}
              </div>

              <div className="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <button
                  type="button"
                  onClick={addFilter}
                  disabled={filters.length >= 20}
                  className="h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                >
                  + Add condition
                </button>

                <div className="flex items-center justify-between gap-4 sm:justify-end">
                  <span className="text-xs text-slate-500">
                    {activeFilterCount} active
                  </span>

                  <button
                    type="button"
                    onClick={clearFilters}
                    disabled={
                      filters.length === 0
                    }
                    className="text-sm font-medium text-slate-500 transition hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
                  >
                    Clear
                  </button>

                  <button
                    type="button"
                    onClick={handleApplyFilters}
                    className="h-10 rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800"
                  >
                    Apply filters
                  </button>
                </div>
              </div>
            </section>

            {/* ==================================================
                ADVANCED ORDERING
            ================================================== */}

            <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
              <div className="border-b border-slate-200 px-5 py-4">
                <h3 className="text-sm font-bold text-slate-900">
                  Advanced Ordering
                </h3>

                <p className="mt-1 text-xs text-slate-500">
                  Sort by multiple columns in
                  priority order.
                </p>
              </div>

              <div className="p-5">
                {sorts.length === 0 ? (
                  <div className="flex min-h-28 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 text-center">
                    <div>
                      <p className="text-sm font-semibold text-slate-700">
                        No advanced ordering
                      </p>

                      <p className="mt-1 text-xs text-slate-500">
                        The normal table header
                        sorting is currently
                        active.
                      </p>
                    </div>
                  </div>
                ) : (
                  <div className="space-y-3">
                    {sorts.map(
                      (
                        sort,
                        index,
                      ) => (
                        <div
                          key={sort.id}
                          className="rounded-xl border border-slate-200 bg-slate-50 p-4"
                        >
                          <div className="mb-3 flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                              Level{" "}
                              {index + 1}
                            </span>

                            <button
                              type="button"
                              onClick={() =>
                                removeSort(
                                  sort.id,
                                )
                              }
                              className="text-xs font-medium text-red-600 hover:text-red-700"
                            >
                              Remove
                            </button>
                          </div>

                          <div className="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_180px_auto]">
                            <select
                              value={
                                sort.field
                              }
                              onChange={(
                                event,
                              ) =>
                                updateSort(
                                  sort.id,
                                  {
                                    field:
                                      event
                                        .target
                                        .value as EnrollmentSort,
                                  },
                                )
                              }
                              className="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                            >
                              {SORT_FIELDS.map(
                                (
                                  field,
                                ) => (
                                  <option
                                    key={
                                      field.value
                                    }
                                    value={
                                      field.value
                                    }
                                  >
                                    {
                                      field.label
                                    }
                                  </option>
                                ),
                              )}
                            </select>

                            <select
                              value={
                                sort.direction
                              }
                              onChange={(
                                event,
                              ) =>
                                updateSort(
                                  sort.id,
                                  {
                                    direction:
                                      event
                                        .target
                                        .value as
                                        | "asc"
                                        | "desc",
                                  },
                                )
                              }
                              className="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                            >
                              <option value="asc">
                                Ascending
                              </option>

                              <option value="desc">
                                Descending
                              </option>
                            </select>

                            <button
                              type="button"
                              onClick={() =>
                                removeSort(
                                  sort.id,
                                )
                              }
                              className="hidden h-10 rounded-lg border border-red-200 bg-white px-3 text-sm font-medium text-red-600 transition hover:bg-red-50 sm:block"
                            >
                              Remove
                            </button>
                          </div>
                        </div>
                      ),
                    )}
                  </div>
                )}
              </div>

              <div className="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <button
                  type="button"
                  onClick={addSort}
                  disabled={sorts.length >= 5}
                  className="h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                >
                  + Add sort level
                </button>

                <div className="flex items-center justify-between gap-4 sm:justify-end">
                  <span className="text-xs text-slate-500">
                    {sorts.length}/5 levels
                  </span>

                  <button
                    type="button"
                    onClick={clearSorts}
                    disabled={
                      sorts.length === 0
                    }
                    className="text-sm font-medium text-slate-500 transition hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
                  >
                    Clear
                  </button>

                  <button
                    type="button"
                    onClick={handleApplyOrdering}
                    className="h-10 rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800"
                  >
                    Apply ordering
                  </button>
                </div>
              </div>
            </section>
          </div>
        </div>

        {/* ==================================================
            MODAL FOOTER
        ================================================== */}

        <div className="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-6 py-3">
          <p className="text-xs text-slate-500">
            {filters.length} filter condition
            {filters.length !== 1
              ? "s"
              : ""}{" "}
            · {sorts.length} ordering level
            {sorts.length !== 1
              ? "s"
              : ""}
          </p>

          <button
            type="button"
            onClick={onClose}
            className="h-9 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
}