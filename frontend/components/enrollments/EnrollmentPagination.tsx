interface EnrollmentPaginationProps {
  currentPage: number;
  from: number | null;
  to: number | null;
  hasMorePages: boolean;
  loading: boolean;

  pageSize: number;
  onPageSizeChange: (pageSize: number) => void;

  onPrevious: () => void;
  onNext: () => void;
}

export default function EnrollmentPagination({
  currentPage,
  from,
  to,
  hasMorePages,
  loading,
  pageSize,
  onPageSizeChange,
  onPrevious,
  onNext,
}: EnrollmentPaginationProps) {
  return (
    <div className="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
      {/* PAGE SIZE */}
      <div className="flex items-center gap-2">
        <label
          htmlFor="page-size"
          className="text-sm text-slate-500"
        >
          Rows per page
        </label>

        <select
          id="page-size"
          value={pageSize}
          onChange={(event) => {
            onPageSizeChange(Number(event.target.value));
          }}
          disabled={loading}
          className="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:opacity-60"
        >
          <option value={10}>10</option>
          <option value={25}>25</option>
          <option value={50}>50</option>
          <option value={100}>100</option>
        </select>
      </div>

      {/* RECORD RANGE */}
      <div className="text-sm text-slate-500">
        {from !== null && to !== null ? (
          <>
            Showing{" "}
            <span className="font-semibold text-slate-700">
              {from}
            </span>{" "}
            to{" "}
            <span className="font-semibold text-slate-700">
              {to}
            </span>
          </>
        ) : (
          "No records"
        )}
      </div>

      {/* NAVIGATION */}
      <div className="flex items-center gap-2">
        <span className="mr-2 text-sm text-slate-500">
          Page{" "}
          <span className="font-semibold text-slate-700">
            {currentPage}
          </span>
        </span>

        <button
          type="button"
          disabled={loading || currentPage <= 1}
          onClick={onPrevious}
          className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
        >
          Previous
        </button>

        <button
          type="button"
          disabled={loading || !hasMorePages}
          onClick={onNext}
          className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
        >
          Next
        </button>
      </div>
    </div>
  );
}