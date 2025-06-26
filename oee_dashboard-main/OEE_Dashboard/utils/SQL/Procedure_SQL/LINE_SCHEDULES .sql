-- ====================================================================
-- OEE System Enhancement Script
-- Target: SQL Server 2012+
-- This script creates new tables, indexes, and stored procedures.
-- It is designed to be run once and is safe to re-run.
-- ====================================================================

-- Section 1: Create New Table for Line Schedules
PRINT 'Section 1: Creating LINE_SCHEDULES table...';
IF OBJECT_ID('dbo.LINE_SCHEDULES', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.LINE_SCHEDULES (
        id INT IDENTITY(1,1) PRIMARY KEY,
        line VARCHAR(50) NOT NULL,
        shift_name VARCHAR(50) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        planned_break_minutes INT NOT NULL DEFAULT 0,
        is_active BIT NOT NULL DEFAULT 1
    );

    CREATE UNIQUE INDEX UQ_line_shift ON dbo.LINE_SCHEDULES (line, shift_name);
    PRINT 'Table LINE_SCHEDULES and its unique index created.';
END
ELSE
BEGIN
    PRINT 'Table LINE_SCHEDULES already exists.';
END
GO

-- Section 2: Create Performance Indexes
PRINT 'Section 2: Creating performance indexes...';
-- Index for Health Check on IOT_TOOLBOX_PARTS
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_Parts_HealthCheck' AND object_id = OBJECT_ID('dbo.IOT_TOOLBOX_PARTS'))
BEGIN
    PRINT 'Creating index IX_Parts_HealthCheck on IOT_TOOLBOX_PARTS...';
    CREATE NONCLUSTERED INDEX IX_Parts_HealthCheck
    ON dbo.IOT_TOOLBOX_PARTS (line, model, part_no);
    PRINT 'Index IX_Parts_HealthCheck created.';
END
ELSE
BEGIN
    PRINT 'Index IX_Parts_HealthCheck already exists.';
END
GO

-- Index for Health Check on IOT_TOOLBOX_PARAMETER
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_Parameter_HealthCheck' AND object_id = OBJECT_ID('dbo.IOT_TOOLBOX_PARAMETER'))
BEGIN
    PRINT 'Creating index IX_Parameter_HealthCheck on IOT_TOOLBOX_PARAMETER...';
    CREATE NONCLUSTERED INDEX IX_Parameter_HealthCheck
    ON dbo.IOT_TOOLBOX_PARAMETER (line, model, part_no);
    PRINT 'Index IX_Parameter_HealthCheck created.';
END
ELSE
BEGIN
    PRINT 'Index IX_Parameter_HealthCheck already exists.';
END
GO

-- Section 3: Create or Update Stored Procedures
PRINT 'Section 3: Creating/Updating Stored Procedures...';
-- SP for getting all schedules
IF OBJECT_ID('dbo.sp_GetSchedules', 'P') IS NOT NULL DROP PROCEDURE dbo.sp_GetSchedules;
GO
CREATE PROCEDURE dbo.sp_GetSchedules
AS
BEGIN
    SET NOCOUNT ON;
    SELECT id, line, shift_name, start_time, end_time, planned_break_minutes, is_active
    FROM dbo.LINE_SCHEDULES
    ORDER BY line, shift_name;
END
GO
PRINT 'Stored Procedure sp_GetSchedules created.';

-- SP for saving (create/update) a schedule
IF OBJECT_ID('dbo.sp_SaveSchedule', 'P') IS NOT NULL DROP PROCEDURE dbo.sp_SaveSchedule;
GO
CREATE PROCEDURE dbo.sp_SaveSchedule
    @id INT,
    @line VARCHAR(50),
    @shift_name VARCHAR(50),
    @start_time TIME,
    @end_time TIME,
    @planned_break_minutes INT,
    @is_active BIT
AS
BEGIN
    SET NOCOUNT ON;
    IF @id = 0
    BEGIN
        INSERT INTO dbo.LINE_SCHEDULES (line, shift_name, start_time, end_time, planned_break_minutes, is_active)
        VALUES (@line, @shift_name, @start_time, @end_time, @planned_break_minutes, @is_active);
    END
    ELSE
    BEGIN
        UPDATE dbo.LINE_SCHEDULES
        SET line = @line,
            shift_name = @shift_name,
            start_time = @start_time,
            end_time = @end_time,
            planned_break_minutes = @planned_break_minutes,
            is_active = @is_active
        WHERE id = @id;
    END
END
GO
PRINT 'Stored Procedure sp_SaveSchedule created.';

-- SP for deleting a schedule
IF OBJECT_ID('dbo.sp_DeleteSchedule', 'P') IS NOT NULL DROP PROCEDURE dbo.sp_DeleteSchedule;
GO
CREATE PROCEDURE dbo.sp_DeleteSchedule
    @id INT
AS
BEGIN
    SET NOCOUNT ON;
    DELETE FROM dbo.LINE_SCHEDULES WHERE id = @id;
END
GO
PRINT 'Stored Procedure sp_DeleteSchedule created.';

-- SP for calculating OEE Pie Chart data
IF OBJECT_ID('dbo.sp_CalculateOEE_PieChart', 'P') IS NOT NULL DROP PROCEDURE dbo.sp_CalculateOEE_PieChart;
GO
CREATE PROCEDURE dbo.sp_CalculateOEE_PieChart
    @StartDate DATE,
    @EndDate DATE,
    @Line VARCHAR(50) = NULL,
    @Model VARCHAR(50) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @TotalPlannedMinutes INT = 0;
    DECLARE @CurrentDate DATE = @StartDate;
    DECLARE @DailyPlannedTime TABLE (PlannedDate DATE, Minutes INT);

    WHILE @CurrentDate <= @EndDate
    BEGIN
        INSERT INTO @DailyPlannedTime (PlannedDate, Minutes)
        SELECT @CurrentDate, SUM(
            CASE WHEN s.end_time >= s.start_time THEN DATEDIFF(MINUTE, s.start_time, s.end_time)
                 ELSE DATEDIFF(MINUTE, s.start_time, s.end_time) + 1440
            END - s.planned_break_minutes
        ) AS DailyMinutes
        FROM dbo.LINE_SCHEDULES s
        WHERE s.is_active = 1 AND (@Line IS NULL OR s.line = @Line)
        GROUP BY s.line;

        SET @CurrentDate = DATEADD(DAY, 1, @CurrentDate);
    END;

    SELECT @TotalPlannedMinutes = ISNULL(SUM(Minutes), 0) FROM @DailyPlannedTime;

    DECLARE @TotalDowntimeMinutes INT;
    SELECT @TotalDowntimeMinutes = ISNULL(SUM(DATEDIFF(MINUTE, stop_begin, stop_end)), 0)
    FROM dbo.IOT_TOOLBOX_STOP_CAUSES
    WHERE log_date BETWEEN @StartDate AND @EndDate AND (@Line IS NULL OR line = @Line);

    DECLARE @TotalRuntimeMinutes INT;
    SET @TotalRuntimeMinutes = @TotalPlannedMinutes - @TotalDowntimeMinutes;
    IF @TotalRuntimeMinutes < 0 SET @TotalRuntimeMinutes = 0;

    DECLARE @TotalFG INT = 0, @TotalDefects INT = 0, @TotalActualOutput INT = 0;
    DECLARE @TotalTheoreticalMinutes DECIMAL(18, 4) = 0;

    ;WITH ProductionData AS (
        SELECT
            SUM(CASE WHEN p.count_type = 'FG' THEN ISNULL(p.count_value, 0) ELSE 0 END) AS FG_Count,
            SUM(CASE WHEN p.count_type <> 'FG' THEN ISNULL(p.count_value, 0) ELSE 0 END) AS Defect_Count,
            MAX(param.planned_output) AS hourly_output
        FROM dbo.IOT_TOOLBOX_PARTS p
        LEFT JOIN dbo.IOT_TOOLBOX_PARAMETER param ON p.model = param.model AND p.part_no = param.part_no AND p.line = param.line
        WHERE p.log_date BETWEEN @StartDate AND @EndDate
          AND (@Line IS NULL OR p.line = @Line)
          AND (@Model IS NULL OR p.model = @Model)
        GROUP BY p.model, p.part_no, p.line
    )
    SELECT
        @TotalFG = ISNULL(SUM(FG_Count), 0),
        @TotalDefects = ISNULL(SUM(Defect_Count), 0),
        @TotalTheoreticalMinutes = ISNULL(SUM( (FG_Count + Defect_Count) * (60.0 / NULLIF(hourly_output, 0)) ), 0)
    FROM ProductionData WHERE hourly_output > 0;

    SET @TotalActualOutput = @TotalFG + @TotalDefects;

    SELECT
        Quality = CAST(ISNULL((@TotalFG * 100.0) / NULLIF(@TotalActualOutput, 0), 0) AS DECIMAL(5,1)),
        Availability = CAST(ISNULL((@TotalRuntimeMinutes * 100.0) / NULLIF(@TotalPlannedMinutes, 0), 0) AS DECIMAL(5,1)),
        Performance = CAST(ISNULL((@TotalTheoreticalMinutes * 100.0) / NULLIF(@TotalRuntimeMinutes, 0), 0) AS DECIMAL(5,1)),
        OEE = CAST(ISNULL(
                ((@TotalRuntimeMinutes * 1.0) / NULLIF(@TotalPlannedMinutes, 0)) *
                ((@TotalTheoreticalMinutes * 1.0) / NULLIF(@TotalRuntimeMinutes, 0)) *
                ((@TotalFG * 1.0) / NULLIF(@TotalActualOutput, 0)) * 100, 0) AS DECIMAL(5,1)),
        FG = @TotalFG,
        Defects = @TotalDefects,
        Runtime = @TotalRuntimeMinutes,
        PlannedTime = @TotalPlannedMinutes,
        Downtime = @TotalDowntimeMinutes,
        ActualOutput = @TotalActualOutput,
        TotalTheoreticalMinutes = CAST(@TotalTheoreticalMinutes AS DECIMAL(18,2));
END;
GO
PRINT 'Stored Procedure sp_CalculateOEE_PieChart created.';

-- SP for getting missing parameters with pagination (Final Optimized Version)
IF OBJECT_ID('dbo.sp_GetMissingParameters', 'P') IS NOT NULL DROP PROCEDURE dbo.sp_GetMissingParameters;
GO
CREATE PROCEDURE dbo.sp_GetMissingParameters
    @PageNumber INT = 1,
    @PageSize INT = 25
AS
BEGIN
    SET NOCOUNT ON;
    
    ;WITH MissingParams AS (
        SELECT
            p.line,
            p.model,
            p.part_no
        FROM (
            SELECT DISTINCT line, model, part_no FROM dbo.IOT_TOOLBOX_PARTS
        ) p
        WHERE NOT EXISTS (
            SELECT 1
            FROM dbo.IOT_TOOLBOX_PARAMETER param
            WHERE param.line = p.line
              AND param.model = p.model
              AND param.part_no = p.part_no
        )
    )
    -- Result Set 1: Total count of all missing params
    SELECT COUNT_BIG(*) AS TotalRecords FROM MissingParams;

    -- Result Set 2: The data for the requested page
    SELECT *
    FROM MissingParams
    ORDER BY line, model, part_no
    OFFSET (@PageNumber - 1) * @PageSize ROWS
    FETCH NEXT @PageSize ROWS ONLY;
END;
GO
PRINT 'Stored Procedure sp_GetMissingParameters created.';

-- Section 4: Insert Sample Data
PRINT 'Section 4: Inserting sample data into LINE_SCHEDULES...';
-- Insert only if the table is empty to avoid duplicates on re-run
IF NOT EXISTS (SELECT 1 FROM dbo.LINE_SCHEDULES)
BEGIN
    INSERT INTO dbo.LINE_SCHEDULES (line, shift_name, start_time, end_time, planned_break_minutes, is_active)
    VALUES
    ('ASSEMBLY', 'DAY', '08:00:00', '17:00:00', 75, 1),
    ('ASSEMBLY', 'NIGHT', '20:00:00', '05:00:00', 75, 1),
    ('PAINT', 'DAY', '07:00:00', '16:00:00', 60, 1),
    ('SPOT', 'DAY', '08:00:00', '17:00:00', 60, 1),
    ('BEND', 'DAY', '08:00:00', '17:00:00', 60, 1),
    ('PRESS', 'DAY', '08:00:00', '17:00:00', 60, 1);
    PRINT 'Sample data inserted.';
END
ELSE
BEGIN
    PRINT 'Sample data already exists in LINE_SCHEDULES.';
END
GO

PRINT '====================================================';
PRINT 'OEE System Enhancement Script completed successfully.';
PRINT '====================================================';
