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

export interface EnrollmentQueryParams {
  page?: number;
  page_size?: number;
  search?: string;
  status?: EnrollmentStatus | "";
  semester?: Semester | "";
  academic_year?: string;
  sort?: EnrollmentSort;
  direction?: "asc" | "desc";
}

export type EnrollmentSort =
  | "id"
  | "student_nim"
  | "student_name"
  | "course_code"
  | "course_name"
  | "semester"
  | "academic_year"
  | "status"
  | "created_at";