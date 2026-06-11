# AI Prompt / Ask KB Feature for UCM Forms

## 1. Document Objective
The objective of this feature is to introduce an AI-powered reporting and insights capability within UCM forms. Users will be able to generate contextual summaries, recommendations, insights, and analytical reports based on the data available within a selected UCM form.
The feature aims to reduce manual analysis effort and assist users in understanding trends, risks, compliance gaps, and recommendations through AI-generated content.

## 2. Business Problem
Many UCM forms contain significant amounts of assessment and compliance data which currently require manual review and interpretation.
Examples include:
- Risk Assessments
- Breach Logs
- DPIA Light Forms
- Compliance Assessments
- Monitoring Compliance Forms
- Governance Assessments

Users currently need to manually review multiple sections and fields to derive conclusions and recommendations.
An AI-powered assistant can automatically analyze the data and provide meaningful insights.

## 3. Scope
The AI Prompt feature will be available for selected UCM Types and will operate only on the data of the currently viewed UCM record.
The feature will not access:
- Other UCM records
- Other organizations
- External data sources
- Unrelated form data

## 4. UCM Type Configuration

### Backend Configuration
A new section will be introduced in the UCM Type Configuration screen.

### New Settings

| Setting | Description |
|---|---|
| Enable AI Insights | Enable/Disable AI for this UCM Type |
| AI Prompt Template | Default prompt template used for AI generation |
| Allow Custom Prompt | Allow users to modify prompts |
| AI Provider | Gemini/OpenAI/Future Providers |
| Show AI Button | Display AI button on frontend |
| Enable Graph Generation | Allow AI to generate charts and graphs |
| Data Redaction Enabled | Remove sensitive data before AI processing |
| AI Disclaimer Text | Beta and legal disclaimer |
| Max Tokens | Maximum prompt size |

## 5. Frontend Integration

### Form View
A new button will be introduced within the UCM Form Detail View.

**Button**
- Ask KB
- Generate AI Insights
- Generate AI Report

The button may be placed:
- Above form tabs
- Beside existing action buttons
- Sticky action panel

## 6. User Workflow

**Step 1**
User opens a UCM record.
Example:
- Monitoring Compliance
- Risk Assessment
- DPIA Light
- Breach Log

**Step 2**
User clicks:
- Generate AI Report

**Step 3**
System collects:
- Field labels
- User responses
- Checklist values
- Risk values
- Comments
- Tags
- Related metadata

from the current UCM record only.

**Step 4**
Data is sanitized and redacted.

**Step 5**
Prompt is generated.

**Step 6**
Prompt is sent to Gemini API.

**Step 7**
AI response is displayed.

## 7. AI Output Types
The system should support generation of:

**Executive Summary**
Example:
> This assessment indicates strong governance controls but highlights gaps in staff awareness and policy reviews.

**Key Findings**
Example:
- 5 High Risk Findings
- 12 Medium Risk Findings

**Recommendations**
Example:
- Implement quarterly governance reviews.
- Improve policy ownership process.

**Risk Analysis**
Example:
High Risk Areas:
- Governance
- Asset Management

**Compliance Gap Analysis**
Example:
Controls Missing:
- Cyber Awareness Program
- Incident Response Testing

**Trend Analysis**
For applicable UCM Types.

**Graph Generation**
AI can generate:
- Pie Charts
- Bar Charts
- Risk Distribution Charts
- Compliance Score Charts

## 8. AI Prompt Framework

### Default Prompt Example

```
Analyze the following UCM assessment data.

Generate:

1. Executive Summary
2. Key Findings
3. Risks Identified
4. Recommendations
5. Improvement Actions

Only use the provided information.
Do not make assumptions.
```

## 9. AI Report Display
Generated content should display:

**Generated On**
Generated: 25-May-2026 10:30 AM

**Last Form Updated**
Last Updated: 25-May-2026 11:15 AM

If the form has changed after report generation:
> This AI report may be outdated. Please regenerate the report.

## 10. Data Privacy & Security

### Mandatory Requirements

**Data Redaction**
Before sending data:
- Email addresses removed
- Phone numbers removed
- Personal names removed
- Sensitive identifiers removed

**Restricted Data**
The following should never be sent:
- User passwords
- Authentication tokens
- Session information
- Protected personal data

**Audit Logging**
Store:
- Generated prompts
- Generated responses
- User who initiated request
- Generation timestamp

## 11. AI Output Storage

**Option A**
Store generated report in database.
Benefits:
- Reuse previous reports
- Faster access
- Historical comparison

**Option B**
Generate on-demand only.

**Recommendation:**
Store generated reports.

## 12. UI Mockup Proposal

### AI Action Section
```
--------------------------------
Generate AI Report
Generate AI Summary
Generate Recommendations
--------------------------------
```

### Generated Insights Panel
```
--------------------------------
Executive Summary

Key Findings

Recommendations

Risk Analysis

Generated On:
--------------------------------
```

## 13. Proof of Concept (POC)

### Phase 1
Target UCM: Monitoring Compliance

### POC Scope
- Gemini Integration
- Generate Summary
- Generate Recommendations
- Generate Risk Analysis
- Store Generated Output

### Success Criteria
- AI analyzes current UCM record.
- Response generated within 10 seconds.
- No PII data exposed.
- Output relevant to assessment data.

## 14. Assumptions
- Gemini API will be used initially.
- API key will be provided by the client.
- AI operates only on the current UCM record.
- AI-generated content is advisory and not authoritative.
- Generated outputs may vary between executions.
- AI features will be clearly marked as Beta.

## 15. Open Questions
- Should generated reports be editable before saving?
- Should AI reports be exportable to PDF?
- Should AI be available for all UCM Types or selected ones only?
- Should users be able to customize prompts?
- Should AI-generated graphs be downloadable?
- Should generated reports be shareable with other users?
