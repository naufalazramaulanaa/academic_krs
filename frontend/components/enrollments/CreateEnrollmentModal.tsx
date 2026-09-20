"use client";

import {
  FormEvent,
  useEffect,
  useState,
} from "react";

import {
  createEnrollment,
  type CreateEnrollmentPayload,
} from "@/lib/enrollments";

import type {
  EnrollmentStatus,
  Semester,
} from "@/types/enrollment";

interface CreateEnrollmentModalProps {
  open: boolean;
  onClose: () => void;
  onCreated: () => void;
}

interface FormState {
  nim: string;
  studentName: string;
  studentEmail: string;

  courseCode: string;
  courseName: string;
  courseCredits: string;

  academicYear: string;
  semester: Semester;
  status: EnrollmentStatus;
}

const INITIAL_FORM: FormState = {
  nim: "",
  studentName: "",
  studentEmail: "",

  courseCode: "",
  courseName: "",
  courseCredits: "3",

  academicYear: "",
  semester: "GANJIL",
  status: "DRAFT",
};

function getErrorMessage(
  error: unknown,
): string {
  if (
    typeof error === "object" &&
    error !== null &&
    "response" in error
  ) {
    const axiosError = error as {
      response?: {
        data?: {
          message?: string;
          errors?: Record<
            string,
            string[] | string
          >;
        };
      };
    };

    const response = axiosError.response;

    if (response?.data?.message) {
      return response.data.message;
    }

    const validationErrors =
      response?.data?.errors;

    if (validationErrors) {
      const firstError =
        Object.values(validationErrors)[0];

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

  return "Gagal membuat enrollment. Silakan coba lagi.";
}

export default function CreateEnrollmentModal({
  open,
  onClose,
  onCreated,
}: CreateEnrollmentModalProps) {
  const [form, setForm] =
    useState<FormState>(INITIAL_FORM);

  const [submitting, setSubmitting] =
    useState(false);

  const [error, setError] =
    useState<string | null>(null);

  useEffect(() => {
    if (!open) {
      return;
    }

    setForm(INITIAL_FORM);
    setError(null);
    setSubmitting(false);
  }, [open]);

  useEffect(() => {
    if (!open) {
      return;
    }

    function handleEscape(
      event: KeyboardEvent,
    ) {
      if (event.key === "Escape") {
        if (!submitting) {
          onClose();
        }
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

  if (!open) {
    return null;
  }

  function updateField(
    field: keyof FormState,
    value: string,
  ) {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));

    setError(null);
  }

  function validate(): string | null {
    const nim = form.nim.trim();
    const studentName =
      form.studentName.trim();
    const studentEmail =
      form.studentEmail.trim();

    const courseCode =
      form.courseCode.trim().toUpperCase();

    const courseName =
      form.courseName.trim();

    const academicYear =
      form.academicYear.trim();

    const credits =
      Number(form.courseCredits);

    if (!/^[0-9]{8,12}$/.test(nim)) {
      return "NIM harus berupa 8–12 digit.";
    }

    if (!studentName) {
      return "Nama mahasiswa wajib diisi.";
    }

    if (!studentEmail) {
      return "Email mahasiswa wajib diisi.";
    }

    if (
      !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
        studentEmail,
      )
    ) {
      return "Format email mahasiswa tidak valid.";
    }

    if (
      !/^[A-Z]{2,4}[0-9]{3,4}$/.test(
        courseCode,
      )
    ) {
      return "Kode mata kuliah harus seperti IF001 atau IF0001.";
    }

    if (!courseName) {
      return "Nama mata kuliah wajib diisi.";
    }

    if (
      !Number.isInteger(credits) ||
      credits < 1 ||
      credits > 6
    ) {
      return "SKS harus berupa angka 1 sampai 6.";
    }

    if (
      !/^[0-9]{4}\/[0-9]{4}$/.test(
        academicYear,
      )
    ) {
      return "Tahun ajaran harus menggunakan format YYYY/YYYY, contoh 2026/2027.";
    }

    return null;
  }

  async function handleSubmit(
    event: FormEvent<HTMLFormElement>,
  ) {
    event.preventDefault();

    const validationError = validate();

    if (validationError) {
      setError(validationError);
      return;
    }

    const payload: CreateEnrollmentPayload = {
      student: {
        nim: form.nim.trim(),
        name: form.studentName.trim(),
        email: form.studentEmail.trim(),
      },

      course: {
        code: form.courseCode
          .trim()
          .toUpperCase(),

        name: form.courseName.trim(),

        credits: Number(
          form.courseCredits,
        ),
      },

      academic_year:
        form.academicYear.trim(),

      semester: form.semester,

      status: form.status,
    };

    try {
      setSubmitting(true);
      setError(null);

      await createEnrollment(payload);

      onCreated();
    } catch (error) {
      setError(
        getErrorMessage(error),
      );
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
      onMouseDown={(event) => {
        if (
          event.target ===
          event.currentTarget
        ) {
          if (!submitting) {
            onClose();
          }
        }
      }}
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-enrollment-title"
        className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl"
      >
        {/* HEADER */}
        <div className="flex items-center justify-between border-b border-slate-200 px-6 py-5">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
              Enrollment
            </p>

            <h2
              id="create-enrollment-title"
              className="mt-1 text-xl font-bold text-slate-950"
            >
              Tambah Enrollment
            </h2>

            <p className="mt-1 text-sm text-slate-500">
              Tambahkan mahasiswa, mata kuliah,
              dan KRS dalam satu transaksi.
            </p>
          </div>

          <button
            type="button"
            onClick={onClose}
            disabled={submitting}
            className="flex h-9 w-9 items-center justify-center rounded-lg text-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
            aria-label="Tutup"
          >
            ×
          </button>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="space-y-6 px-6 py-6">
            {/* ERROR */}
            {error && (
              <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                <p className="text-sm font-semibold text-red-800">
                  Gagal membuat enrollment
                </p>

                <p className="mt-1 text-sm leading-6 text-red-700">
                  {error}
                </p>
              </div>
            )}

            {/* STUDENT */}
            <section>
              <div className="mb-4">
                <h3 className="text-sm font-bold text-slate-950">
                  Data Mahasiswa
                </h3>

                <p className="mt-1 text-xs text-slate-500">
                  Jika NIM sudah ada, backend akan
                  memperbarui data mahasiswa tersebut.
                </p>
              </div>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                  <label
                    htmlFor="create-nim"
                    className="mb-1.5 block text-sm font-medium text-slate-700"
                  >
                    NIM
                  </label>

                  <input
                    id="create-nim"
                    value={form.nim}
                    onChange={(event) =>
                      updateField(
                        "nim",
                        event.target.value,
                      )
                    }
                    placeholder="2026000001"
                    maxLength={12}
                    disabled={submitting}
                    className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 disabled:bg-slate-100"
                  />
                </div>

                <div>
                  <label
                    htmlFor="create-student-name"
                    className="mb-1.5 block text-sm font-medium text-slate-700"
                  >
                    Nama Mahasiswa
                  </label>

                  <input
                    id="create-student-name"
                    value={form.studentName}
                    onChange={(event) =>
                      updateField(
                        "studentName",
                        event.target.value,
                      )
                    }
                    placeholder="Ahmad Fauzan"
                    maxLength={100}
                    disabled={submitting}
                    className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 disabled:bg-slate-100"
                  />
                </div>

                <div className="md:col-span-2">
                  <label
                    htmlFor="create-student-email"
                    className="mb-1.5 block text-sm font-medium text-slate-700"
                  >
                    Email
                  </label>

                  <input
                    id="create-student-email"
                    type="email"
                    value={form.studentEmail}
                    onChange={(event) =>
                      updateField(
                        "studentEmail",
                        event.target.value,
                      )
                    }
                    placeholder="ahmad@example.com"
                    maxLength={150}
                    disabled={submitting}
                    className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 disabled:bg-slate-100"
                  />
                </div>
              </div>
            </section>

            {/* COURSE */}
            <section className="border-t border-slate-200 pt-6">
              <div className="mb-4">
                <h3 className="text-sm font-bold text-slate-950">
                  Data Mata Kuliah
                </h3>

                <p className="mt-1 text-xs text-slate-500">
                  Jika kode mata kuliah sudah ada,
                  backend akan memperbarui data course.
                </p>
              </div>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                  <label
                    htmlFor="create-course-code"
                    className="mb-1.5 block text-sm font-medium text-slate-700"
                  >
                    Kode Mata Kuliah
                  </label>

                  <input
                    id="create-course-code"
                    value={form.courseCode}
                    onChange={(event) =>
                      updateField(
                        "courseCode",
                        event.target.value,
                      )
                    }
                    placeholder="IF0001"
                    maxLength={8}
                    disabled={submitting}
                    className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm uppercase outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 disabled:bg-slate-100"
                  />
                </div>

                <div>
                  <label
                    htmlFor="create-course-credits"
                    className="mb-1.5 block text-sm font-medium text-slate-700"
                  >
                    SKS
                  </label>

                  <select
                    id="create-course-credits"
                    value={form.courseCredits}
                    onChange={(event) =>
                      updateField(
                        "courseCredits",
                        event.target.value,
                      )
                    }
                    disabled={submitting}
                    className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 disabled:bg-slate-100"
                  >
                    <option value="1">
                      1 SKS
                    </option>

                    <option value="2">
                      2 SKS
                    </option>

                    <option value="3">
                      3 SKS
                    </option>

                    <option value="4">
                      4 SKS
                    </option>

                    <option value="5">
                      5 SKS
                    </option>

                    <option value="6">
                      6 SKS
                    </option>
                  </select>
                </div>

                <div className="md:col-span-2">
                  <label
                    htmlFor="create-course-name"
                    className="mb-1.5 block text-sm font-medium text-slate-700"
                  >
                    Nama Mata Kuliah
                  </label>

                  <input
                    id="create-course-name"
                    value={form.courseName}
                    onChange={(event) =>
                      updateField(
                        "courseName",
                        event.target.value,
                      )
                    }
                    placeholder="Pemrograman Web"
                    maxLength={120}
                    disabled={submitting}
                    className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 disabled:bg-slate-100"
                  />
                </div>
              </div>
            </section>

            {/* ENROLLMENT */}
            <section className="border-t border-slate-200 pt-6">
              <div className="mb-4">
                <h3 className="text-sm font-bold text-slate-950">
                  Data KRS
                </h3>

                <p className="mt-1 text-xs text-slate-500">
                  Data enrollment akan dibuat setelah
                  student dan course berhasil diproses.
                </p>
              </div>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                  <label
                    htmlFor="create-academic-year"
                    className="mb-1.5 block text-sm font-medium text-slate-700"
                  >
                    Tahun Ajaran
                  </label>

                  <input
                    id="create-academic-year"
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
                    htmlFor="create-semester"
                    className="mb-1.5 block text-sm font-medium text-slate-700"
                  >
                    Semester
                  </label>

                  <select
                    id="create-semester"
                    value={form.semester}
                    onChange={(event) =>
                      updateField(
                        "semester",
                        event.target.value,
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
                    htmlFor="create-status"
                    className="mb-1.5 block text-sm font-medium text-slate-700"
                  >
                    Status
                  </label>

                  <select
                    id="create-status"
                    value={form.status}
                    onChange={(event) =>
                      updateField(
                        "status",
                        event.target.value,
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
                : "Simpan Enrollment"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}