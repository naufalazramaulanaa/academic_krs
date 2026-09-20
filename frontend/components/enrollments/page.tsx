"use client";

import { useEffect, useMemo, useState } from "react";

import EnrollmentFormModal from "@/components/enrollments/EnrollmentFormModal";
import EnrollmentPagination from "@/components/enrollments/EnrollmentPagination";
import EnrollmentTable from "@/components/enrollments/EnrollmentTable";
import EnrollmentToolbar from "@/components/enrollments/EnrollmentToolbar";

import { useEnrollments } from "@/hooks/useEnrollments";

import {
  createEnrollment,
  deleteEnrollment,
  updateEnrollment,
} from "@/lib/enrollments";

import type {
  Enrollment,
  EnrollmentSort,
  EnrollmentStatus,
  Semester,
} from "@/types/enrollment";

const PAGE_SIZE = 25;

type MutationErrorResponse = {
  response?: {
    status?: number;
    data?: {
      message?: string;
      errors?: Record<
        string,
        string[] | string
      >;
    };
  };
};

export default function EnrollmentsPage() {
  const [page, setPage] = useState(1);

  const [searchInput, setSearchInput] =
    useState("");

  const [search, setSearch] =
    useState("");

  const [status, setStatus] =
    useState<EnrollmentStatus | "">("");

  const [semester, setSemester] =
    useState<Semester | "">("");

  const [academicYear, setAcademicYear] =
    useState("");

  const [sort, setSort] =
    useState<EnrollmentSort>("created_at");

  const [direction, setDirection] =
    useState<"asc" | "desc">("desc");

  /*
   * CRUD modal state
   */
  const [modalOpen, setModalOpen] =
    useState(false);

  const [modalMode, setModalMode] = useState<
    "create" | "edit"
  >("create");

  const [selectedEnrollment, setSelectedEnrollment] =
    useState<Enrollment | null>(null);

  /*
   * Create / Update state
   */
  const [mutationLoading, setMutationLoading] =
    useState(false);

  const [mutationError, setMutationError] =
    useState<string | null>(null);

  /*
   * Delete state
   */
  const [deleteTarget, setDeleteTarget] =
    useState<Enrollment | null>(null);

  const [deleteLoading, setDeleteLoading] =
    useState(false);

  /*
   * Global feedback
   */
  const [feedback, setFeedback] = useState<{
    type: "success" | "error";
    message: string;
  } | null>(null);

  /*
   * Debounce search.
   */
  useEffect(() => {
    const timer = window.setTimeout(() => {
      setSearch(searchInput);
      setPage(1);
    }, 350);

    return () => {
      window.clearTimeout(timer);
    };
  }, [searchInput]);

  /*
   * Auto-hide feedback.
   */
  useEffect(() => {
    if (!feedback) {
      return;
    }

    const timer = window.setTimeout(() => {
      setFeedback(null);
    }, 4000);

    return () => {
      window.clearTimeout(timer);
    };
  }, [feedback]);

  /*
   * Stable query object.
   */
  const queryParams = useMemo(
    () => ({
      page,
      page_size: PAGE_SIZE,
      search,
      status,
      semester,
      academic_year: academicYear,
      sort,
      direction,
    }),
    [
      page,
      search,
      status,
      semester,
      academicYear,
      sort,
      direction,
    ],
  );

  const {
    data,
    meta,
    loading,
    error,
    refetch,
  } = useEnrollments(queryParams);

  /*
   * Sorting
   */
  function handleSort(
    column: EnrollmentSort,
  ) {
    setPage(1);

    if (sort === column) {
      setDirection((current) =>
        current === "asc"
          ? "desc"
          : "asc",
      );

      return;
    }

    setSort(column);
    setDirection("asc");
  }

  /*
   * Filters
   */
  function handleStatusChange(
    value: EnrollmentStatus | "",
  ) {
    setStatus(value);
    setPage(1);
  }

  function handleSemesterChange(
    value: Semester | "",
  ) {
    setSemester(value);
    setPage(1);
  }

  function handleAcademicYearChange(
    value: string,
  ) {
    setAcademicYear(value);
    setPage(1);
  }

  function handleReset() {
    setSearchInput("");
    setSearch("");

    setStatus("");
    setSemester("");
    setAcademicYear("");

    setSort("created_at");
    setDirection("desc");

    setPage(1);
  }

  /*
   * API error normalizer
   */
  function getApiErrorMessage(
    error: unknown,
    fallback: string,
  ): string {
    if (
      typeof error === "object" &&
      error !== null &&
      "response" in error
    ) {
      const axiosError =
        error as MutationErrorResponse;

      const response =
        axiosError.response;

      /*
       * Duplicate enrollment
       */
      if (response?.status === 409) {
        return (
          response.data?.message ??
          "Enrollment sudah terdaftar untuk kombinasi tersebut."
        );
      }

      /*
       * Validation error
       */
      if (response?.status === 422) {
        const errors =
          response.data?.errors;

        if (errors) {
          const firstError =
            Object.values(errors)
              .flat()
              .find(
                (message) =>
                  typeof message ===
                  "string",
              );

          if (firstError) {
            return firstError;
          }
        }

        return (
          response.data?.message ??
          "Data yang dikirim tidak valid."
        );
      }

      /*
       * Not found
       */
      if (response?.status === 404) {
        return (
          response.data?.message ??
          "Enrollment tidak ditemukan."
        );
      }

      return (
        response.data?.message ??
        fallback
      );
    }

    if (error instanceof Error) {
      return error.message;
    }

    return fallback;
  }

  /*
   * CREATE
   */
  function openCreateModal() {
    setMutationError(null);
    setSelectedEnrollment(null);
    setModalMode("create");
    setModalOpen(true);
  }

  /*
   * EDIT
   */
  function openEditModal(
    enrollment: Enrollment,
  ) {
    setMutationError(null);
    setSelectedEnrollment(enrollment);
    setModalMode("edit");
    setModalOpen(true);
  }

  function closeModal() {
    if (mutationLoading) {
      return;
    }

    setModalOpen(false);
    setSelectedEnrollment(null);
    setMutationError(null);
  }

  async function handleCreate(
    payload: {
      student: {
        nim: string;
        name: string;
        email: string;
      };

      course: {
        code: string;
        name: string;
        credits: number;
      };

      academic_year: string;
      semester: Semester;
      status: EnrollmentStatus;
    },
  ) {
    setMutationLoading(true);
    setMutationError(null);

    try {
      await createEnrollment(payload);

      setModalOpen(false);
      setSelectedEnrollment(null);

      setFeedback({
        type: "success",
        message:
          "Enrollment berhasil dibuat.",
      });

      /*
       * Return to first page so the newly
       * created enrollment can be visible.
       */
      setPage(1);

      refetch();
    } catch (error) {
      setMutationError(
        getApiErrorMessage(
          error,
          "Gagal membuat enrollment.",
        ),
      );
    } finally {
      setMutationLoading(false);
    }
  }

  /*
   * UPDATE
   */
  async function handleUpdate(
    payload: {
      academic_year: string;
      semester: Semester;
      status: EnrollmentStatus;
    },
  ) {
    if (!selectedEnrollment) {
      return;
    }

    setMutationLoading(true);
    setMutationError(null);

    try {
      await updateEnrollment(
        selectedEnrollment.id,
        payload,
      );

      setModalOpen(false);
      setSelectedEnrollment(null);

      setFeedback({
        type: "success",
        message:
          "Enrollment berhasil diperbarui.",
      });

      refetch();
    } catch (error) {
      setMutationError(
        getApiErrorMessage(
          error,
          "Gagal memperbarui enrollment.",
        ),
      );
    } finally {
      setMutationLoading(false);
    }
  }

  /*
   * DELETE
   */
  function openDeleteDialog(
    enrollment: Enrollment,
  ) {
    setDeleteTarget(enrollment);
  }

  function closeDeleteDialog() {
    if (deleteLoading) {
      return;
    }

    setDeleteTarget(null);
  }

  async function handleDelete() {
    if (!deleteTarget) {
      return;
    }

    setDeleteLoading(true);

    try {
      await deleteEnrollment(
        deleteTarget.id,
      );

      setDeleteTarget(null);

      setFeedback({
        type: "success",
        message:
          "Enrollment berhasil dihapus.",
      });

      /*
       * If the current page had only one row,
       * move back one page.
       */
      if (
        data.length === 1 &&
        page > 1
      ) {
        setPage((current) =>
          Math.max(1, current - 1),
        );
      }

      refetch();
    } catch (error) {
      setFeedback({
        type: "error",
        message: getApiErrorMessage(
          error,
          "Gagal menghapus enrollment.",
        ),
      });
    } finally {
      setDeleteLoading(false);
    }
  }

  return (
    <main className="min-h-screen bg-slate-50">
      <div className="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
        <div className="space-y-6">

          {/* =========================
              HEADER
          ========================= */}
          <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <p className="text-sm font-medium text-slate-500">
                Academic KRS
              </p>

              <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                Enrollment Management
              </h1>

              <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Browse, search, filter, sort, and manage
                student course enrollments.
              </p>
            </div>

            {/* ADD ENROLLMENT */}
            <button
              type="button"
              onClick={openCreateModal}
              className="inline-flex h-10 shrink-0 items-center justify-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
            >
              <span className="mr-2 text-base">
                +
              </span>

              Add Enrollment
            </button>
          </div>

          {/* =========================
              FEEDBACK
          ========================= */}
          {feedback && (
            <div
              className={`rounded-xl border px-4 py-3 ${
                feedback.type ===
                "success"
                  ? "border-emerald-200 bg-emerald-50"
                  : "border-red-200 bg-red-50"
              }`}
            >
              <div
                className={`text-sm font-medium ${
                  feedback.type ===
                  "success"
                    ? "text-emerald-800"
                    : "text-red-800"
                }`}
              >
                {feedback.message}
              </div>
            </div>
          )}

          {/* =========================
              FILTER / SEARCH
          ========================= */}
          <EnrollmentToolbar
            search={searchInput}
            status={status}
            semester={semester}
            academicYear={academicYear}
            onSearchChange={
              setSearchInput
            }
            onStatusChange={
              handleStatusChange
            }
            onSemesterChange={
              handleSemesterChange
            }
            onAcademicYearChange={
              handleAcademicYearChange
            }
            onReset={handleReset}
          />

          {/* =========================
              LIST ERROR
          ========================= */}
          {error && (
            <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
              <div className="text-sm font-semibold text-red-800">
                Failed to load enrollments
              </div>

              <div className="mt-1 text-sm text-red-700">
                {error}
              </div>
            </div>
          )}

          {/* =========================
              TABLE
          ========================= */}
          <EnrollmentTable
            data={data}
            loading={loading}
            sort={sort}
            direction={direction}
            onSort={handleSort}
            onEdit={openEditModal}
            onDelete={
              openDeleteDialog
            }
          />

          {/* =========================
              PAGINATION
          ========================= */}
          {meta && (
            <EnrollmentPagination
              currentPage={
                meta.current_page
              }
              from={meta.from}
              to={meta.to}
              hasMorePages={
                meta.has_more_pages
              }
              loading={loading}
              onPrevious={() =>
                setPage((current) =>
                  Math.max(
                    1,
                    current - 1,
                  ),
                )
              }
              onNext={() =>
                setPage(
                  (current) =>
                    current + 1,
                )
              }
            />
          )}
        </div>
      </div>

      {/* =========================
          CREATE / EDIT MODAL
      ========================= */}
      <EnrollmentFormModal
        open={modalOpen}
        mode={modalMode}
        enrollment={
          selectedEnrollment
        }
        submitting={
          mutationLoading
        }
        error={mutationError}
        onClose={closeModal}
        onCreate={handleCreate}
        onUpdate={handleUpdate}
      />

      {/* =========================
          DELETE CONFIRMATION
      ========================= */}
      {deleteTarget && (
        <div
          className="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 p-4"
          role="dialog"
          aria-modal="true"
          aria-labelledby="delete-dialog-title"
        >
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">

            <h2
              id="delete-dialog-title"
              className="text-lg font-bold text-slate-950"
            >
              Delete Enrollment?
            </h2>

            <p className="mt-2 text-sm leading-6 text-slate-600">
              Enrollment{" "}
              <span className="font-semibold text-slate-900">
                {
                  deleteTarget.student_nim
                }
              </span>{" "}
              untuk course{" "}
              <span className="font-semibold text-slate-900">
                {
                  deleteTarget.course_code
                }
              </span>{" "}
              akan dihapus.
            </p>

            <p className="mt-2 text-xs text-slate-500">
              Student dan course tidak
              akan ikut dihapus.
            </p>

            <div className="mt-6 flex justify-end gap-3">
              <button
                type="button"
                onClick={
                  closeDeleteDialog
                }
                disabled={
                  deleteLoading
                }
                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              >
                Cancel
              </button>

              <button
                type="button"
                onClick={
                  handleDelete
                }
                disabled={
                  deleteLoading
                }
                className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {deleteLoading
                  ? "Deleting..."
                  : "Delete"}
              </button>
            </div>
          </div>
        </div>
      )}
    </main>
  );
}