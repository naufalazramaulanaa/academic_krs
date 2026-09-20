"use client";

import { useEffect, useState } from "react";

import { updateEnrollment } from "@/lib/enrollments";

import type {
  Enrollment,
  EnrollmentStatus,
  Semester,
} from "@/types/enrollment";

interface EditEnrollmentModalProps {
  open: boolean;
  enrollment: Enrollment | null;
  onClose: () => void;
  onUpdated: () => void;
}

interface FormState {
  academicYear: string;
  semester: Semester;
  status: EnrollmentStatus;
}

const INITIAL_FORM: FormState = {
  academicYear: "",
  semester: "GANJIL",
  status: "DRAFT",
};

function getErrorMessage(error: unknown): string {
  if (
    typeof error === "object" &&
    error !== null &&
    "response" in error
  ) {
    const axiosError = error as {
      response?: {
        data?: {
          message?: string;
          errors?: Record<string, string[] | string>;
        };
      };
    };

    const response = axiosError.response;

    if (response?.data?.message) {
      return response.data.message;
    }

    const validationErrors = response?.data?.errors;

    if (validationErrors) {
      const firstError = Object.values(validationErrors)[0];

      if (Array.isArray(firstError)) {
        return firstError[0] ?? "Data tidak valid.";
      }

      if (typeof firstError === "string") {
        return firstError;
      }
    }
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "Gagal memperbarui enrollment. Silakan coba lagi.";
}

export default function EditEnrollmentModal({
  open,
  enrollment,
  onClose,
  onUpdated,
}: EditEnrollmentModalProps) {
  const [form, setForm] =
    useState<FormState>(INITIAL_FORM);

  const [submitting, setSubmitting] =
    useState(false);

  const [error, setError] =
    useState<string | null>(null);

  useEffect(() => {
    if (!open || !enrollment) {
      return;
    }

    setForm({
      academicYear: enrollment.academic_year,
      semester: enrollment.semester,
      status: enrollment.status,
    });

    setError(null);
    setSubmitting(false);
  }, [open, enrollment]);

  useEffect(() => {
    if (!open) {
      return;
    }

    function handleEscape(event: KeyboardEvent) {
      if (event.key === "Escape" && !submitting) {
        onClose();
      }
    }

    window.addEventListener(
      "keydown",
      handleEscape,
    );

    return () => {
      window.removeEventListener(
        "keydown",
        handleEscape,
      );
    };
  }, [open, onClose, submitting]);

  if (!open || !enrollment) {
    return null;
  }

  function updateField<K extends keyof FormState>(
    field: K,
    value: FormState[K],
  ) {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));

    setError(null);
  }

  function validate(): string | null {
    const academicYear =
      form.academicYear.trim();

    if (
      !/^[0-9]{4}\/[0-9]{4}$/.test(
        academicYear,
      )
    ) {
      return (
        "Tahun ajaran harus menggunakan format " +
        "YYYY/YYYY, contoh 2026/2027."
      );
    }

    return null;
  }

  async function handleSubmit(
    event: React.FormEvent<HTMLFormElement>,
  ) {
    event.preventDefault();

    const validationError = validate();

    if (validationError) {
      setError(validationError);
      return;
    }

    try {
      setSubmitting(true);
      setError(null);

      await updateEnrollment(
        enrollment.id,
        {
          academic_year:
            form.academicYear.trim(),
          semester: form.semester,
          status: form.status,
        },
      );

      onUpdated();
    } catch (error) {
      setError(getErrorMessage(error));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
      onMouseDown={(event) => {
        if (
          event.target === event.currentTarget &&
          !submitting
        ) {
          onClose();
        }
      }}
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-enrollment-title"
        className="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl"
      >
        {/* HEADER */}
        <div className="flex items-center justify-between border-b border-slate-200 px-6 py-5">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
              Enrollment
            </p>

            <h2
              id="edit-enrollment-title"
              className="mt-1 text-xl font-bold text-slate-950"
            >
              Edit Enrollment
            </h2>

            <p className="mt-1 text-sm text-slate-500">
              Perbarui periode dan status KRS.
            </p>
          </div>

          <button
            type="button"
            onClick={onClose}
            disabled={submitting}
            aria-label="Tutup"
            className="flex h-9 w-9 items-center justify-center rounded-lg text-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            ×
          </button>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="space-y-6 px-6 py-6">
            {/* INFORMATION */}
            <section className="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <h3 className="text-sm font-bold text-slate-950">
                Informasi Enrollment
              </h3>

              <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                  <p className="text-xs font-medium text-slate-500">
                    NIM
                  </p>

                  <p className="mt-1 text-sm font-semibold text-slate-900">
                    {enrollment.student_nim}
                  </p>
                </div>

                <div>
                  <p className="text-xs font-medium text-slate-500">
                    Mahasiswa
                  </p>

                  <p className="mt-1 text-sm font-semibold text-slate-900">
                    {enrollment.student_name}
                  </p>
                </div>

                <div>
                  <p className="text-xs font-medium text-slate-500">
                    Mata Kuliah
                  </p>

                  <p className="mt-1 text-sm font-semibold text-slate-900">
                    {enrollment.course_code}
                    {" — "}
                    {enrollment.course_name}
                  </p>
                </div>

                <div>
                  <p className="text-xs font-medium text-slate-500">
                    SKS
                  </p>

                  <p className="mt-1 text-sm font-semibold text-slate-900">
                    {enrollment.course_credits}
                  </p>
                </div>
              </div>
            </section>

            {/* ERROR */}
            {error && (
              <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                <p className="text-sm font-semibold text-red-800">
                  Gagal memperbarui enrollment
                </p>

                <p className="mt-1 text-sm leading-6 text-red-700">
                  {error}
                </p>
              </div>
            )}

            {/* FORM */}
            <section>
              <h3 className="text-sm font-bold text-slate-950">
                Data KRS
              </h3>

              <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                  <label
                    htmlFor="edit-academic-year"
                    className="mb-1.5 block text-sm font-medium text-slate-700"
                  >
                    Tahun Ajaran
                  </label>

                  <input
                    id="edit-academic-year"
                    value={form.academicYear}
                    onChange={(event) =>
                      updateField(
                        "academicYear",
                        event.target.value,
                      )
                    }
                    placeholder="2026/2027"
                    maxLength={9}
                    disabled={submitting}
                    className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 disabled:bg-slate-100"
                  />
                </div>

                <div>
                  <label
                    htmlFor="edit-semester"
                    className="mb-1.5 block text-sm font-medium text-slate-700"
                  >
                    Semester
                  </label>

                  <select
                    id="edit-semester"
                    value={form.semester}
                    onChange={(event) =>
                      updateField(
                        "semester",
                        event.target.value as Semester,
                      )
                    }
                    disabled={submitting}
                    className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 disabled:bg-slate-100"
                  >
                    <option value="GANJIL">
                      Ganjil
                    </option>

                    <option value="GENAP">
                      Genap
                    </option>
                  </select>
                </div>

                <div>
                  <label
                    htmlFor="edit-status"
                    className="mb-1.5 block text-sm font-medium text-slate-700"
                  >
                    Status
                  </label>

                  <select
                    id="edit-status"
                    value={form.status}
                    onChange={(event) =>
                      updateField(
                        "status",
                        event.target.value as EnrollmentStatus,
                      )
                    }
                    disabled={submitting}
                    className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 disabled:bg-slate-100"
                  >
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
                </div>
              </div>
            </section>
          </div>

          {/* FOOTER */}
          <div className="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
            <button
              type="button"
              onClick={onClose}
              disabled={submitting}
              className="h-10 rounded-lg border border-slate-300 bg-white px-5 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Batal
            </button>

            <button
              type="submit"
              disabled={submitting}
              className="h-10 rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {submitting
                ? "Menyimpan..."
                : "Simpan Perubahan"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}