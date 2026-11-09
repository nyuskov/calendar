export type ColorKey = "cornflower" | "mint" | "lavanda" | "brand";

export type ViewMode = "week" | "month";

export interface Employee {
  id: number;
  last_name: string;
  first_name: string;
  middle_name: string | null;
  role: string;
  weekly_hours: number;
  avatar_color: string;
  full_name: string;
  initials: string;
}

export interface ShiftTemplate {
  id: number;
  name: string;
  code: string;
  start_time: string;
  end_time: string;
  color_key: ColorKey;
  sort_order: number;
  duration_hours: number;
}

export interface Shift {
  id: number;
  employee_id: number;
  shift_template_id: number | null;
  date: string;
  start_time: string;
  end_time: string;
  duration_hours: number;
  color_key: ColorKey;
}

export interface ApiList<T> {
  data: T[];
}
export interface MenuItem {
  label: string;
  value: string | number;
  danger?: boolean;
}
