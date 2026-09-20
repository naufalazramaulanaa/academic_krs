export type EnrollmentStatus =
  | "DRAFT"
  | "SUBMITTED"
  | "APPROVED"
  | "REJECTED";

export type Semester = "GANJIL" | "GENAP";

export interface EnrollmentStudent {
  id: number;
  nim: string;
  name: string;
  email: string;
}

export interface EnrollmentCourse {
  id: number;
  code: string;
  name: string;
  credits: number;
}

export interface Enrollment {
  id: number;
  student_id: number;
  course_id: number;

  academic_year: string;
  semester: Semester;
  status: EnrollmentStatus;

  created_at: string;
  updated_at: string;

  student_nim: string;
  student_name: string;
  student_email: string;

  course_code: string;
  course_name: string;
  course_credits: number;
}

export interface EnrollmentPaginationMeta {
  current_page: number;
  per_page: number;
  from: number | null;
  to: number | null;
  has_more_pages: boolean;
}

export interface EnrollmentPaginationLinks {
  prev: string | null;
  next: string | null;
}

export interface EnrollmentListResponse {
  message: string;
  data: Enrollment[];
  meta: EnrollmentPaginationMeta;
  links: EnrollmentPaginationLinks;
}

/*
 * ==========================================================
 * LEGACY SORT
 * ==========================================================
 */

export type EnrollmentSort =
  | "id"
  | "student_nim"
  | "student_name"
  | "student_email"
  | "course_code"
  | "course_name"
  | "course_credits"
  | "semester"
  | "academic_year"
  | "status"
  | "created_at";

/*
 * ==========================================================
 * ADVANCED FILTER
 * ==========================================================
 */

export type AdvancedFilterLogic = "AND" | "OR";

export type AdvancedFilterField =
  | "student_nim"
  | "student_name"
  | "student_email"
  | "course_code"
  | "course_name"
  | "course_credits"
  | "academic_year"
  | "semester"
  | "status";

export type TextFilterOperator =
  | "contains"
  | "startsWith"
  | "equal"
  | "in";

export type CreditsFilterOperator =
  | "equal"
  | "in"
  | "between"
  | "gt"
  | "gte"
  | "lt"
  | "lte";

export type AcademicYearFilterOperator =
  | "equal"
  | "in"
  | "between";

export type EnumFilterOperator =
  | "equal"
  | "in";

export type AdvancedFilterOperator =
  | TextFilterOperator
  | CreditsFilterOperator
  | AcademicYearFilterOperator
  | EnumFilterOperator;

export interface AdvancedFilterItem {
  id: string;

  field: AdvancedFilterField;

  operator: AdvancedFilterOperator;

  /*
   * String for:
   * - contains
   * - startsWith
   * - equal
   *
   * Array for:
   * - in
   * - between
   */
  value: string | string[];
}

export interface AdvancedFilterGroup {
  logic: AdvancedFilterLogic;
  items: AdvancedFilterItem[];
}

/*
 * ==========================================================
 * ADVANCED ORDERING
 * ==========================================================
 */

export interface AdvancedSortItem {
  id: string;

  field: EnrollmentSort;

  direction: "asc" | "desc";
}

/*
 * ==========================================================
 * API QUERY
 * ==========================================================
 */

export interface EnrollmentQueryParams {
  page?: number;

  /*
   * Frontend naming.
   *
   * src/lib/enrollments.ts converts this into
   * Laravel's page_size.
   */
  pageSize?: number;

  search?: string;

  status?: EnrollmentStatus | "";

  semester?: Semester | "";

  academic_year?: string;

  sort?: EnrollmentSort;

  direction?: "asc" | "desc";

  filters?: AdvancedFilterGroup;

  sorts?: AdvancedSortItem[];
}