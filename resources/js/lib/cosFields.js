export const COS_FIELDS = [
    { key: "deceased_name", label: "Deceased Name" },
    { key: "deceased_address", label: "Deceased Address" },
    { key: "date_of_death", label: "Date of Death" },
    { key: "place_of_death", label: "Place of Death" },
    { key: "date_of_depository", label: "Date of Depository" },
    { key: "burial_place", label: "Burial Place" },
    { key: "applicant_name", label: "Applicant Name" },
    { key: "applicant_address", label: "Applicant Address" },
    { key: "relationship", label: "Relationship" },
];

export function fieldLabel(key) {
    return COS_FIELDS.find((f) => f.key === key)?.label ?? key;
}

export function formatLongDate(value) {
    if (!value) return "";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
}