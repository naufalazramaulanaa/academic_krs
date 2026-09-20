"use client";

import { useEffect, useMemo, useState } from "react";

import AdvancedQueryModal from "@/components/enrollments/AdvancedQueryModal";
import CreateEnrollmentModal from "@/components/enrollments/CreateEnrollmentModal";
import EditEnrollmentModal from "@/components/enrollments/EditEnrollmentModal";
import EnrollmentPagination from "@/components/enrollments/EnrollmentPagination";
import EnrollmentTable from "@/components/enrollments/EnrollmentTable";
import EnrollmentToolbar from "@/components/enrollments/EnrollmentToolbar";

import { useEnrollments } from "@/hooks/useEnrollments";

import { deleteEnrollment } from "@/lib/enrollments";

import { getApiErrorMessage } from "@/lib/error";

import type {
  AdvancedFilterGroup,
  AdvancedSortItem,
  Enrollment,
  EnrollmentSort,
  EnrollmentStatus,
  Semester,
} from "@/types/enrollment";

const DEFAULT_PAGE_SIZE = 25;

export default function EnrollmentsPage() {
  /* ==========================================================
     PAGINATION
  ========================================================== */

  const [page, setPage] = useState(1);

  const [pageSize, setPageSize] = useState(DEFAULT_PAGE_SIZE);

  /* ==========================================================
     SEARCH
  ========================================================== */

  const [searchInput, setSearchInput] = useState("");

  const [search, setSearch] = useState("");

  /* ==========================================================
     QUICK FILTERS
  ========================================================== */

  const [status, setStatus] = useState<EnrollmentStatus | "">("");

  const [semester, setSemester] = useState<Semester | "">("");

  const [academicYear, setAcademicYear] = useState("");

  /* ==========================================================
     LEGACY TABLE SORTING
  ========================================================== */

  const [sort, setSort] = useState<EnrollmentSort>("created_at");

  const [direction, setDirection] = useState<"asc" | "desc">("desc");

  /* ==========================================================
     ADVANCED QUERY
  ========================================================== */

  const [advancedQueryOpen, setAdvancedQueryOpen] = useState(false);

  const [advancedFilters, setAdvancedFilters] =
    useState<AdvancedFilterGroup | null>(null);

  const [advancedSorts, setAdvancedSorts] = useState<AdvancedSortItem[]>([]);

  /* ==========================================================
     CRUD MODALS
  ========================================================== */

  const [createModalOpen, setCreateModalOpen] = useState(false);

  const [editModalOpen, setEditModalOpen] = useState(false);

  const [selectedEnrollment, setSelectedEnrollment] =
    useState<Enrollment | null>(null);

  /* ==========================================================
     DELETE STATE
  ========================================================== */

  const [deletingId, setDeletingId] = useState<number | null>(null);

  /* ==========================================================
     SEARCH DEBOUNCE
  ========================================================== */

  useEffect(() => {
    const timer = window.setTimeout(() => {
      setSearch(searchInput);
      setPage(1);
    }, 350);

    return () => {
      window.clearTimeout(timer);
    };
  }, [searchInput]);

  /* ==========================================================
     QUERY PARAMS
  ========================================================== */

  const queryParams = useMemo(
    () => ({
      page,

      pageSize,

      search,

      status,

      semester,

      academic_year: academicYear,

      /*
       * Legacy sorting hanya dikirim apabila
       * advanced sorting tidak aktif.
       */
      ...(advancedSorts.length === 0
        ? {
            sort,
            direction,
          }
        : {}),

      /*
       * Advanced filters.
       */
      ...(advancedFilters
        ? {
            filters: advancedFilters,
          }
        : {}),

      /*
       * Advanced multi-column ordering.
       */
      ...(advancedSorts.length > 0
        ? {
            sorts: advancedSorts,
          }
        : {}),
    }),
    [
      page,
      pageSize,
      search,
      status,
      semester,
      academicYear,
      sort,
      direction,
      advancedFilters,
      advancedSorts,
    ],
  );

  /* ==========================================================
     FETCH ENROLLMENTS
  ========================================================== */

  const { data, meta, loading, error, refetch } = useEnrollments(queryParams);

  /* ==========================================================
     TABLE SORT
  ========================================================== */

  function handleSort(column: EnrollmentSort) {
    /*
     * Kalau advanced ordering sedang aktif,
     * klik sorting table akan kembali ke
     * legacy/simple sorting.
     */
    if (advancedSorts.length > 0) {
      setAdvancedSorts([]);
    }

    setPage(1);

    if (sort === column) {
      setDirection((current) => (current === "asc" ? "desc" : "asc"));

      return;
    }

    setSort(column);
    setDirection("asc");
  }

  /* ==========================================================
     QUICK FILTER HANDLERS
  ========================================================== */

  function handleStatusChange(value: EnrollmentStatus | "") {
    setStatus(value);
    setPage(1);
  }

  function handleSemesterChange(value: Semester | "") {
    setSemester(value);
    setPage(1);
  }

  function handleAcademicYearChange(value: string) {
    setAcademicYear(value);
    setPage(1);
  }

  /* ==========================================================
     PAGE SIZE
  ========================================================== */

  function handlePageSizeChange(newPageSize: number) {
    setPageSize(newPageSize);
    setPage(1);
  }

  /* ==========================================================
     ADVANCED QUERY
  ========================================================== */

  function handleAdvancedQueryApply(
    filters: AdvancedFilterGroup | null,
    sorts: AdvancedSortItem[],
  ) {
    setAdvancedFilters(filters);
    setAdvancedSorts(sorts);

    /*
     * Query berubah -> kembali ke page 1.
     */
    setPage(1);

    setAdvancedQueryOpen(false);
  }

  /* ==========================================================
     CLEAR ADVANCED QUERY
  ========================================================== */

  function handleClearAdvancedQuery() {
    setAdvancedFilters(null);
    setAdvancedSorts([]);
    setPage(1);
  }

  /* ==========================================================
     RESET EVERYTHING
  ========================================================== */

  function handleReset() {
    /*
     * Search
     */
    setSearchInput("");
    setSearch("");

    /*
     * Quick filters
     */
    setStatus("");
    setSemester("");
    setAcademicYear("");

    /*
     * Legacy sorting
     */
    setSort("created_at");
    setDirection("desc");

    /*
     * Advanced query
     */
    setAdvancedFilters(null);
    setAdvancedSorts([]);

    /*
     * Pagination
     */
    setPage(1);
  }

  /* ==========================================================
     CREATE
  ========================================================== */

  function handleCreate() {
    setCreateModalOpen(true);
  }

  function handleCreated() {
    setCreateModalOpen(false);

    /*
     * Setelah create, tampilkan data
     * dari page pertama.
     */
    setPage(1);

    refetch();
  }

  /* ==========================================================
     EDIT
  ========================================================== */

  function handleEdit(enrollment: Enrollment) {
    setSelectedEnrollment(enrollment);
    setEditModalOpen(true);
  }

  function handleEditClose() {
    setEditModalOpen(false);
    setSelectedEnrollment(null);
  }

  function handleUpdated() {
    setEditModalOpen(false);
    setSelectedEnrollment(null);

    refetch();
  }

  /* ==========================================================
     DELETE
  ========================================================== */

  async function handleDelete(enrollment: Enrollment) {
    /*
     * Jangan izinkan delete request
     * bersamaan untuk row yang sama.
     */
    if (deletingId !== null) {
      return;
    }

    const confirmed = window.confirm(
      [
        "Yakin ingin menghapus enrollment ini?",
        "",
        `NIM: ${enrollment.student_nim}`,
        `Student: ${enrollment.student_name}`,
        `Course: ${enrollment.course_code} - ${enrollment.course_name}`,
        `Academic Year: ${enrollment.academic_year}`,
        `Semester: ${enrollment.semester}`,
      ].join("\n"),
    );

    if (!confirmed) {
      return;
    }

    try {
      setDeletingId(enrollment.id);

      await deleteEnrollment(enrollment.id);

      /*
       * Refresh data setelah delete.
       */
      refetch();
    } catch (error) {
      console.error("Failed to delete enrollment:", error);

      window.alert(
        getApiErrorMessage(
          error,
          "Gagal menghapus enrollment. Silakan coba lagi.",
        ),
      );
    } finally {
      setDeletingId(null);
    }
  }

  /* ==========================================================
     ADVANCED QUERY ACTIVE
  ========================================================== */

  const advancedQueryActive =
    advancedFilters !== null || advancedSorts.length > 0;

  /* ==========================================================
     RENDER
  ========================================================== */

  return (
    <main className="min-h-screen bg-slate-50">
      <div className="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
        <div className="space-y-6">
          {/* ==================================================
              HEADER
          ================================================== */}

          <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <p className="text-sm font-medium text-slate-500">Academic KRS</p>

              <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                Enrollment Management
              </h1>

              <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Browse, search, filter, sort, and manage student course
                enrollments.
              </p>
            </div>

            {/* CREATE BUTTON */}

            <button
              type="button"
              onClick={handleCreate}
              className="inline-flex h-10 items-center justify-center rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800"
            >
              + Tambah
            </button>
          </div>

          {/* ==================================================
              TOOLBAR
          ================================================== */}

          <EnrollmentToolbar
            search={searchInput}
            status={status}
            semester={semester}
            academicYear={academicYear}
            onSearchChange={setSearchInput}
            onStatusChange={handleStatusChange}
            onSemesterChange={handleSemesterChange}
            onAcademicYearChange={handleAcademicYearChange}
            onReset={handleReset}
            onAdvancedQuery={() => setAdvancedQueryOpen(true)}
            advancedQueryActive={advancedQueryActive}
            onCreate={handleCreate}
          />

          {/* ==================================================
              ACTIVE ADVANCED QUERY SUMMARY
          ================================================== */}

          {advancedQueryActive && (
            <div className="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
              <div className="flex flex-wrap items-center gap-2">
                <span className="rounded-full bg-slate-950 px-2.5 py-1 text-xs font-semibold text-white">
                  Advanced Query Active
                </span>

                {advancedFilters && (
                  <span className="text-xs text-slate-500">
                    {advancedFilters.items.length} filter condition
                    {advancedFilters.items.length !== 1 ? "s" : ""}
                    {" · "}
                    Logic:{" "}
                    <span className="font-semibold text-slate-700">
                      {advancedFilters.logic}
                    </span>
                  </span>
                )}

                {advancedSorts.length > 0 && (
                  <span className="text-xs text-slate-500">
                    {advancedSorts.length} ordering level
                    {advancedSorts.length !== 1 ? "s" : ""}
                  </span>
                )}
              </div>

              <button
                type="button"
                onClick={handleClearAdvancedQuery}
                className="text-left text-sm font-medium text-slate-600 transition hover:text-slate-950 sm:text-right"
              >
                Clear advanced query
              </button>
            </div>
          )}

          {/* ==================================================
              ERROR
          ================================================== */}

          {error && (
            <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
              <div className="text-sm font-semibold text-red-800">
                Failed to load enrollments
              </div>

              <div className="mt-1 text-sm text-red-700">{error}</div>
            </div>
          )}

          {/* ==================================================
              TABLE
          ================================================== */}

          <EnrollmentTable
            data={data}
            loading={loading}
            sort={sort}
            direction={direction}
            onSort={handleSort}
            onEdit={handleEdit}
            onDelete={handleDelete}
          />

          {/* ==================================================
              PAGINATION
          ================================================== */}

          {meta && (
            <EnrollmentPagination
              currentPage={meta.current_page}
              from={meta.from}
              to={meta.to}
              hasMorePages={meta.has_more_pages}
              loading={loading || deletingId !== null}
              pageSize={pageSize}
              onPageSizeChange={handlePageSizeChange}
              onPrevious={() => {
                setPage((current) => Math.max(1, current - 1));
              }}
              onNext={() => {
                if (!meta.has_more_pages) {
                  return;
                }

                setPage((current) => current + 1);
              }}
            />
          )}
        </div>
      </div>

      {/* ====================================================
          CREATE MODAL
      ==================================================== */}

      <CreateEnrollmentModal
        open={createModalOpen}
        onClose={() => setCreateModalOpen(false)}
        onCreated={handleCreated}
      />

      {/* ====================================================
          EDIT MODAL
      ==================================================== */}

      <EditEnrollmentModal
        open={editModalOpen}
        enrollment={selectedEnrollment}
        onClose={handleEditClose}
        onUpdated={handleUpdated}
      />

      {/* ====================================================
          ADVANCED QUERY MODAL
      ==================================================== */}

      <AdvancedQueryModal
        open={advancedQueryOpen}
        initialFilters={advancedFilters}
        initialSorts={advancedSorts}
        onClose={() => setAdvancedQueryOpen(false)}
        onApply={handleAdvancedQueryApply}
      />
    </main>
  );
}
