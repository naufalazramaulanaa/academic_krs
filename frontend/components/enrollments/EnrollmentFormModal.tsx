"use client";

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";

import type {
  Enrollment,
  EnrollmentStatus,
  Semester,
} from "@/types/enrollment";

const enrollmentFormSchema = z.object({
  nim: z
    .string()
    .trim()
    .regex(/^[0-9]{8,12}$/, "NIM harus terdiri dari 8–12 digit."),

  student_name: z
    .string()
    .trim()
    .min(3, "Nama minimal 3 karakter.")
    .max(100, "Nama maksimal 100 karakter."),

  student_email: z
    .string()
    .trim()
    .email("Email tidak valid.")
    .max(150, "Email maksimal 150 karakter."),

  course_code: z
    .string()
    .trim()
    .regex(
      /^[A-Z]{2,4}[0-9]{3}$/,
      "Course code harus seperti IF301 atau TI001.",
    ),

  course_name: z
    .string()
    .trim()
    .min(3, "Nama course minimal 3 karakter.")
    .max(120, "Nama course maksimal 120 karakter."),

  course_credits: z
    .number({
      error: "Credits wajib diisi.",
    })
    .int("Credits harus berupa angka bulat.")
    .min(1, "Credits minimal 1.")
    .max(6, "Credits maksimal 6."),

  academic_year: z
    .string()
    .trim()
    .regex(
      /^[0-9]{4}\/[0-9]{4}$/,
      "Academic year harus seperti 2026/2027.",
    ),

  semester: z.enum(["GANJIL", "GENAP"]),

  status: z.enum([
    "DRAFT",
    "SUBMITTED",
    "APPROVED",
    "REJECTED",
  ]),
});

type EnrollmentFormValues = z.infer<
  typeof enrollmentFormSchema
>;

interface EnrollmentFormModalProps {
  open: boolean;
  mode: "create" | "edit";
  enrollment?: Enrollment | null;
  submitting: boolean;
  error: string | null;

  onClose: () => void;

  onCreate: (payload: {
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
  }) => Promise<void>;

  onUpdate: (payload: {
    academic_year: string;
    semester: Semester;
    status: EnrollmentStatus;
  }) => Promise<void>;
}

const DEFAULT_VALUES: EnrollmentFormValues = {
  nim: "",
  student_name: "",
  student_email: "",
  course_code: "",
  course_name: "",
  course_credits: 3,
  academic_year: "",
  semester: "GANJIL",
  status: "DRAFT",
};

export default function EnrollmentFormModal({
  open,
  mode,
  enrollment,
  submitting,
  error,
  onClose,
  onCreate,
  onUpdate,
}: EnrollmentFormModalProps) {
  const isEdit = mode === "edit";

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<EnrollmentFormValues>({
    resolver: zodResolver(enrollmentFormSchema),
    defaultValues: DEFAULT_VALUES,
  });

  useEffect(() => {
    if (!open) {
      return;
    }

    if (isEdit && enrollment) {
      reset({
        nim: enrollment.student_nim,
        student_name: enrollment.student_name,
        student_email: enrollment.student_email,
        course_code: enrollment.course_code,
        course_name: enrollment.course_name,
        course_credits: enrollment.course_credits,
        academic_year: enrollment.academic_year,
        semester: enrollment.semester,
        status: enrollment.status,
      });

      return;
    }

    reset(DEFAULT_VALUES);
  }, [open, isEdit, enrollment, reset]);

  if (!open) {
    return null;
  }

  async function onSubmit(values: EnrollmentFormValues) {
    if (isEdit) {
      await onUpdate({
        academic_year: values.academic_year,
        semester: values.semester,
        status: values.status,
      });

      return;
    }

    await onCreate({
      student: {
        nim: values.nim,
        name: values.student_name,
        email: values.student_email,
      },
      course: {
        code: values.course_code,
        name: values.course_name,
        credits: values.course_credits,
      },
      academic_year: values.academic_year,
      semester: values.semester,
      status: values.status,
    });
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="enrollment-modal-title"
    >
      <div className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div className="flex items-start justify-between border-b border-slate-200 px-6 py-5">
          <div>
            <h2
              id="enrollment-modal-title"
              className="text-lg font-bold text-slate-950"
            >
              {isEdit
                ? "Edit Enrollment"
                : "Add Enrollment"}
            </h2>

            <p className="mt-1 text-sm text-slate-500">
              {isEdit
                ? "Update enrollment information."
                : "Create student, course, and enrollment in one transaction."}
            </p>
          </div>

          <button
            type="button"
            onClick={onClose}
            disabled={submitting}
            className="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
            aria-label="Close"
          >
            ✕
          </button>
        </div>

        <form
          onSubmit={handleSubmit(onSubmit)}
          className="space-y-6 p-6"
        >
          {error && (
            <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
              <div className="text-sm font-semibold text-red-800">
                Operation failed
              </div>

              <div className="mt-1 text-sm text-red-700">
                {error}
              </div>
            </div>
          )}

          {!isEdit && (
            <>
              {/* Student */}
              <section>
                <div className="mb-4">
                  <h3 className="text-sm font-bold text-slate-900">
                    Student
                  </h3>

                  <p className="mt-1 text-xs text-slate-500">
                    Existing student with the same NIM will be
                    updated by the backend.
                  </p>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <FormField
                    label="NIM"
                    error={errors.nim?.message}
                  >
                    <input
                      {...register("nim")}
                      placeholder="2026112233"
                      className={inputClass(
                        !!errors.nim,
                      )}
                    />
                  </FormField>

                  <FormField
                    label="Name"
                    error={errors.student_name?.message}
                  >
                    <input
                      {...register("student_name")}
                      placeholder="Ahmad Fauzi"
                      className={inputClass(
                        !!errors.student_name,
                      )}
                    />
                  </FormField>

                  <FormField
                    label="Email"
                    error={errors.student_email?.message}
                    fullWidth
                  >
                    <input
                      type="email"
                      {...register("student_email")}
                      placeholder="ahmad@example.com"
                      className={inputClass(
                        !!errors.student_email,
                      )}
                    />
                  </FormField>
                </div>
              </section>

              {/* Course */}
              <section className="border-t border-slate-100 pt-6">
                <div className="mb-4">
                  <h3 className="text-sm font-bold text-slate-900">
                    Course
                  </h3>

                  <p className="mt-1 text-xs text-slate-500">
                    Existing course with the same code will be
                    updated by the backend.
                  </p>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <FormField
                    label="Course Code"
                    error={errors.course_code?.message}
                  >
                    <input
                      {...register("course_code")}
                      placeholder="IF301"
                      className={inputClass(
                        !!errors.course_code,
                      )}
                    />
                  </FormField>

                  <FormField
                    label="Credits"
                    error={errors.course_credits?.message}
                  >
                    <input
                      type="number"
                      min={1}
                      max={6}
                      {...register("course_credits", {
                        valueAsNumber: true,
                      })}
                      className={inputClass(
                        !!errors.course_credits,
                      )}
                    />
                  </FormField>

                  <FormField
                    label="Course Name"
                    error={errors.course_name?.message}
                    fullWidth
                  >
                    <input
                      {...register("course_name")}
                      placeholder="Kecerdasan Buatan"
                      className={inputClass(
                        !!errors.course_name,
                      )}
                    />
                  </FormField>
                </div>
              </section>
            </>
          )}

          {isEdit && (
            <section>
              <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                  Enrollment
                </div>

                <div className="mt-2 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                  <div>
                    <span className="text-slate-500">
                      Student
                    </span>
                    <div className="font-medium text-slate-900">
                      {enrollment?.student_nim} —{" "}
                      {enrollment?.student_name}
                    </div>
                  </div>

                  <div>
                    <span className="text-slate-500">
                      Course
                    </span>
                    <div className="font-medium text-slate-900">
                      {enrollment?.course_code} —{" "}
                      {enrollment?.course_name}
                    </div>
                  </div>
                </div>
              </div>
            </section>
          )}

          {/* Enrollment */}
          <section className="border-t border-slate-100 pt-6">
            <div className="mb-4">
              <h3 className="text-sm font-bold text-slate-900">
                Enrollment
              </h3>
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
              <FormField
                label="Academic Year"
                error={errors.academic_year?.message}
              >
                <input
                  {...register("academic_year")}
                  placeholder="2026/2027"
                  maxLength={9}
                  className={inputClass(
                    !!errors.academic_year,
                  )}
                />
              </FormField>

              <FormField
                label="Semester"
                error={errors.semester?.message}
              >
                <select
                  {...register("semester")}
                  className={inputClass(
                    !!errors.semester,
                  )}
                >
                  <option value="GANJIL">
                    Ganjil
                  </option>
                  <option value="GENAP">
                    Genap
                  </option>
                </select>
              </FormField>

              <FormField
                label="Status"
                error={errors.status?.message}
              >
                <select
                  {...register("status")}
                  className={inputClass(
                    !!errors.status,
                  )}
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
              </FormField>
            </div>
          </section>

          <div className="flex justify-end gap-3 border-t border-slate-200 pt-5">
            <button
              type="button"
              onClick={onClose}
              disabled={submitting}
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Cancel
            </button>

            <button
              type="submit"
              disabled={submitting}
              className="rounded-lg bg-slate-950 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {submitting
                ? "Saving..."
                : isEdit
                  ? "Save Changes"
                  : "Create Enrollment"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

function FormField({
  label,
  error,
  children,
  fullWidth = false,
}: {
  label: string;
  error?: string;
  children: React.ReactNode;
  fullWidth?: boolean;
}) {
  return (
    <div className={fullWidth ? "md:col-span-2" : ""}>
      <label className="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
        {label}
      </label>

      {children}

      {error && (
        <p className="mt-1 text-xs text-red-600">
          {error}
        </p>
      )}
    </div>
  );
}

function inputClass(hasError: boolean) {
  return [
    "h-10 w-full rounded-lg border bg-white px-3 text-sm text-slate-900 outline-none transition",
    "focus:ring-2 focus:ring-slate-200",
    hasError
      ? "border-red-300 focus:border-red-400"
      : "border-slate-300 focus:border-slate-500",
  ].join(" ");
}