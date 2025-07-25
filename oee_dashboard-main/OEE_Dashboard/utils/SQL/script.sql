USE [master]
GO
/****** Object:  Database [oee_db]    Script Date: 27/06/2025 13:50:07 ******/
CREATE DATABASE [oee_db]
 CONTAINMENT = NONE
 ON  PRIMARY 
( NAME = N'oee_db', FILENAME = N'C:\Program Files\Microsoft SQL Server\MSSQL16.MSSQLSERVER\MSSQL\DATA\oee_db.mdf' , SIZE = 73728KB , MAXSIZE = UNLIMITED, FILEGROWTH = 65536KB )
 LOG ON 
( NAME = N'oee_db_log', FILENAME = N'C:\Program Files\Microsoft SQL Server\MSSQL16.MSSQLSERVER\MSSQL\DATA\oee_db_log.ldf' , SIZE = 270336KB , MAXSIZE = 2048GB , FILEGROWTH = 65536KB )
 WITH CATALOG_COLLATION = DATABASE_DEFAULT, LEDGER = OFF
GO
ALTER DATABASE [oee_db] SET COMPATIBILITY_LEVEL = 160
GO
IF (1 = FULLTEXTSERVICEPROPERTY('IsFullTextInstalled'))
begin
EXEC [oee_db].[dbo].[sp_fulltext_database] @action = 'enable'
end
GO
ALTER DATABASE [oee_db] SET ANSI_NULL_DEFAULT OFF 
GO
ALTER DATABASE [oee_db] SET ANSI_NULLS OFF 
GO
ALTER DATABASE [oee_db] SET ANSI_PADDING OFF 
GO
ALTER DATABASE [oee_db] SET ANSI_WARNINGS OFF 
GO
ALTER DATABASE [oee_db] SET ARITHABORT OFF 
GO
ALTER DATABASE [oee_db] SET AUTO_CLOSE OFF 
GO
ALTER DATABASE [oee_db] SET AUTO_SHRINK OFF 
GO
ALTER DATABASE [oee_db] SET AUTO_UPDATE_STATISTICS ON 
GO
ALTER DATABASE [oee_db] SET CURSOR_CLOSE_ON_COMMIT OFF 
GO
ALTER DATABASE [oee_db] SET CURSOR_DEFAULT  GLOBAL 
GO
ALTER DATABASE [oee_db] SET CONCAT_NULL_YIELDS_NULL OFF 
GO
ALTER DATABASE [oee_db] SET NUMERIC_ROUNDABORT OFF 
GO
ALTER DATABASE [oee_db] SET QUOTED_IDENTIFIER OFF 
GO
ALTER DATABASE [oee_db] SET RECURSIVE_TRIGGERS OFF 
GO
ALTER DATABASE [oee_db] SET  DISABLE_BROKER 
GO
ALTER DATABASE [oee_db] SET AUTO_UPDATE_STATISTICS_ASYNC OFF 
GO
ALTER DATABASE [oee_db] SET DATE_CORRELATION_OPTIMIZATION OFF 
GO
ALTER DATABASE [oee_db] SET TRUSTWORTHY OFF 
GO
ALTER DATABASE [oee_db] SET ALLOW_SNAPSHOT_ISOLATION OFF 
GO
ALTER DATABASE [oee_db] SET PARAMETERIZATION SIMPLE 
GO
ALTER DATABASE [oee_db] SET READ_COMMITTED_SNAPSHOT OFF 
GO
ALTER DATABASE [oee_db] SET HONOR_BROKER_PRIORITY OFF 
GO
ALTER DATABASE [oee_db] SET RECOVERY FULL 
GO
ALTER DATABASE [oee_db] SET  MULTI_USER 
GO
ALTER DATABASE [oee_db] SET PAGE_VERIFY CHECKSUM  
GO
ALTER DATABASE [oee_db] SET DB_CHAINING OFF 
GO
ALTER DATABASE [oee_db] SET FILESTREAM( NON_TRANSACTED_ACCESS = OFF ) 
GO
ALTER DATABASE [oee_db] SET TARGET_RECOVERY_TIME = 60 SECONDS 
GO
ALTER DATABASE [oee_db] SET DELAYED_DURABILITY = DISABLED 
GO
ALTER DATABASE [oee_db] SET ACCELERATED_DATABASE_RECOVERY = OFF  
GO
EXEC sys.sp_db_vardecimal_storage_format N'oee_db', N'ON'
GO
ALTER DATABASE [oee_db] SET QUERY_STORE = ON
GO
ALTER DATABASE [oee_db] SET QUERY_STORE (OPERATION_MODE = READ_WRITE, CLEANUP_POLICY = (STALE_QUERY_THRESHOLD_DAYS = 30), DATA_FLUSH_INTERVAL_SECONDS = 900, INTERVAL_LENGTH_MINUTES = 60, MAX_STORAGE_SIZE_MB = 1000, QUERY_CAPTURE_MODE = AUTO, SIZE_BASED_CLEANUP_MODE = AUTO, MAX_PLANS_PER_QUERY = 200, WAIT_STATS_CAPTURE_MODE = ON)
GO
USE [oee_db]
GO
/****** Object:  User [verymaron01]    Script Date: 27/06/2025 13:50:07 ******/
CREATE USER [verymaron01] FOR LOGIN [verymaron01] WITH DEFAULT_SCHEMA=[dbo]
GO
ALTER ROLE [db_owner] ADD MEMBER [verymaron01]
GO
ALTER ROLE [db_datareader] ADD MEMBER [verymaron01]
GO
ALTER ROLE [db_datawriter] ADD MEMBER [verymaron01]
GO
/****** Object:  Table [dbo].[IOT_TOOLBOX_PARTS]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[IOT_TOOLBOX_PARTS](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[log_date] [date] NOT NULL,
	[log_time] [time](7) NOT NULL,
	[line] [varchar](50) NOT NULL,
	[model] [varchar](50) NOT NULL,
	[part_no] [varchar](50) NOT NULL,
	[count_value] [int] NOT NULL,
	[count_type] [varchar](50) NOT NULL,
	[note] [varchar](max) NULL,
	[lot_no] [nvarchar](100) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  View [dbo].[vw_LatestPartCounts]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- VIEW: Latest Part Counts per Line/Model
CREATE VIEW [dbo].[vw_LatestPartCounts] AS
SELECT
    line,
    model,
    part_no,
    MAX(log_date) AS last_date,
    SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
    SUM(CASE WHEN count_type = 'NG' THEN count_value ELSE 0 END) AS NG
FROM dbo.IOT_TOOLBOX_PARTS
GROUP BY line, model, part_no;
GO
/****** Object:  View [dbo].[vw_DailyPartSummary]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE VIEW [dbo].[vw_DailyPartSummary]
AS
SELECT
    log_date,
    model,
    part_no,
    count_type,
    SUM(count_value) AS total_count
FROM dbo.IOT_TOOLBOX_PARTS
GROUP BY log_date, model, part_no, count_type;

GO
/****** Object:  Table [dbo].[IOT_TOOLBOX_STOP_CAUSES]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[IOT_TOOLBOX_STOP_CAUSES](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[log_date] [date] NOT NULL,
	[stop_begin] [datetime] NOT NULL,
	[stop_end] [datetime] NOT NULL,
	[line] [nvarchar](50) NOT NULL,
	[machine] [nvarchar](50) NOT NULL,
	[cause] [nvarchar](255) NOT NULL,
	[note] [nvarchar](255) NOT NULL,
	[recovered_by] [nvarchar](100) NOT NULL,
	[duration]  AS (datediff(minute,[stop_begin],[stop_end])) PERSISTED,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  View [dbo].[vw_StopByMachine]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE VIEW [dbo].[vw_StopByMachine]
AS
SELECT
    line,
    machine,
    COUNT(*) AS stop_count,
    SUM(duration) AS total_minutes
FROM dbo.IOT_TOOLBOX_STOP_CAUSES
GROUP BY line, machine;

GO
/****** Object:  View [dbo].[vw_Daily_Part_Summary]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE VIEW [dbo].[vw_Daily_Part_Summary] AS
SELECT 
    log_date,
    model,
    part_no,
    count_type,
    SUM(count_value) AS total_count
FROM IOT_TOOLBOX_PARTS
GROUP BY log_date, model, part_no, count_type;

GO
/****** Object:  View [dbo].[vw_Stop_Cause_Summary]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE VIEW [dbo].[vw_Stop_Cause_Summary] AS
SELECT 
    log_date,
    line,
    machine,
    cause,
    SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS total_minutes
FROM IOT_TOOLBOX_STOP_CAUSES
GROUP BY log_date, line, machine, cause;

GO
/****** Object:  Table [dbo].[IOT_TOOLBOX_PARAMETER]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[IOT_TOOLBOX_PARAMETER](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[line] [varchar](50) NOT NULL,
	[model] [varchar](100) NOT NULL,
	[part_no] [varchar](100) NOT NULL,
	[planned_output] [int] NOT NULL,
	[updated_at] [datetime] NULL,
	[sap_no] [varchar](100) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [uc_line_model_part_sap] UNIQUE NONCLUSTERED 
(
	[line] ASC,
	[model] ASC,
	[part_no] ASC,
	[sap_no] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[IOT_TOOLBOX_USER_LOGS]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[IOT_TOOLBOX_USER_LOGS](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[action_by] [varchar](100) NULL,
	[action_type] [varchar](20) NULL,
	[target_user] [varchar](100) NULL,
	[detail] [nvarchar](255) NULL,
	[created_at] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[IOT_TOOLBOX_USERS]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[IOT_TOOLBOX_USERS](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[username] [varchar](100) NOT NULL,
	[password] [nvarchar](255) NOT NULL,
	[role] [varchar](50) NULL,
	[created_at] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[username] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[LINE_SCHEDULES]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[LINE_SCHEDULES](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[line] [varchar](50) NOT NULL,
	[shift_name] [varchar](50) NOT NULL,
	[start_time] [time](7) NOT NULL,
	[end_time] [time](7) NOT NULL,
	[planned_break_minutes] [int] NOT NULL,
	[is_active] [bit] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Object:  Index [IX_Parameter_HealthCheck]    Script Date: 27/06/2025 13:50:07 ******/
CREATE NONCLUSTERED INDEX [IX_Parameter_HealthCheck] ON [dbo].[IOT_TOOLBOX_PARAMETER]
(
	[line] ASC,
	[model] ASC,
	[part_no] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Object:  Index [idx_parts_part_no]    Script Date: 27/06/2025 13:50:07 ******/
CREATE NONCLUSTERED INDEX [idx_parts_part_no] ON [dbo].[IOT_TOOLBOX_PARTS]
(
	[part_no] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Object:  Index [IX_Parts_HealthCheck]    Script Date: 27/06/2025 13:50:07 ******/
CREATE NONCLUSTERED INDEX [IX_Parts_HealthCheck] ON [dbo].[IOT_TOOLBOX_PARTS]
(
	[line] ASC,
	[model] ASC,
	[part_no] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Object:  Index [idx_stop_date]    Script Date: 27/06/2025 13:50:07 ******/
CREATE NONCLUSTERED INDEX [idx_stop_date] ON [dbo].[IOT_TOOLBOX_STOP_CAUSES]
(
	[log_date] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Object:  Index [UQ_line_shift]    Script Date: 27/06/2025 13:50:07 ******/
CREATE UNIQUE NONCLUSTERED INDEX [UQ_line_shift] ON [dbo].[LINE_SCHEDULES]
(
	[line] ASC,
	[shift_name] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
ALTER TABLE [dbo].[IOT_TOOLBOX_PARAMETER] ADD  DEFAULT (getdate()) FOR [updated_at]
GO
ALTER TABLE [dbo].[IOT_TOOLBOX_PARTS] ADD  CONSTRAINT [DF_parts_note]  DEFAULT ('-') FOR [note]
GO
ALTER TABLE [dbo].[IOT_TOOLBOX_USER_LOGS] ADD  DEFAULT (getdate()) FOR [created_at]
GO
ALTER TABLE [dbo].[IOT_TOOLBOX_USERS] ADD  DEFAULT ('user') FOR [role]
GO
ALTER TABLE [dbo].[IOT_TOOLBOX_USERS] ADD  DEFAULT (getdate()) FOR [created_at]
GO
ALTER TABLE [dbo].[LINE_SCHEDULES] ADD  DEFAULT ((0)) FOR [planned_break_minutes]
GO
ALTER TABLE [dbo].[LINE_SCHEDULES] ADD  DEFAULT ((1)) FOR [is_active]
GO
/****** Object:  StoredProcedure [dbo].[sp_AddUser]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[sp_AddUser]
    @username VARCHAR(100),
    @password NVARCHAR(255),
    @role VARCHAR(50) = 'user'
AS
BEGIN
    INSERT INTO IOT_TOOLBOX_USERS (username, password, role, created_at)
    VALUES (@username, @password, @role, GETDATE());
END

GO
/****** Object:  StoredProcedure [dbo].[sp_CalculateOEE_LineChart]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE PROCEDURE [dbo].[sp_CalculateOEE_LineChart]
    @StartDate DATE,
    @EndDate DATE,
    @Line VARCHAR(50) = NULL,
    @Model VARCHAR(50) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    -- 1. Create a temporary table of all dates in the specified range
    DECLARE @DateSeries TABLE (LogDate DATE PRIMARY KEY);
    DECLARE @CurrentDate DATE = @StartDate;
    WHILE @CurrentDate <= @EndDate
    BEGIN
        INSERT INTO @DateSeries (LogDate) VALUES (@CurrentDate);
        SET @CurrentDate = DATEADD(DAY, 1, @CurrentDate);
    END;

    -- 2. Use CTEs to pre-aggregate data for each day
    WITH
    DailyPlannedTime AS (
        -- This CTE now correctly identifies which shifts ACTUALLY ran on a given day
        SELECT
            aps.log_date AS LogDate,
            ISNULL(SUM(
                CASE WHEN s.end_time >= s.start_time THEN DATEDIFF(MINUTE, s.start_time, s.end_time)
                     ELSE DATEDIFF(MINUTE, s.start_time, s.end_time) + 1440
                END - s.planned_break_minutes
            ), 0) AS PlannedMinutes
        FROM (
            SELECT DISTINCT p.log_date, s.id AS schedule_id
            FROM dbo.IOT_TOOLBOX_PARTS p
            JOIN dbo.LINE_SCHEDULES s ON p.line = s.line AND s.is_active = 1
            WHERE p.log_date BETWEEN @StartDate AND @EndDate AND (@Line IS NULL OR p.line = @Line)
              AND (
                   (s.start_time <= s.end_time AND p.log_time BETWEEN s.start_time AND s.end_time) OR
                   (s.start_time > s.end_time AND (p.log_time >= s.start_time OR p.log_time < s.end_time))
              )
        ) aps
        JOIN dbo.LINE_SCHEDULES s ON aps.schedule_id = s.id
        GROUP BY aps.log_date
    ),
    DailyDowntime AS (
        SELECT
            log_date,
            ISNULL(SUM(DATEDIFF(MINUTE, stop_begin, stop_end)), 0) AS DowntimeMinutes
        FROM dbo.IOT_TOOLBOX_STOP_CAUSES
        WHERE log_date BETWEEN @StartDate AND @EndDate AND (@Line IS NULL OR line = @Line)
        GROUP BY log_date
    ),
    DailyProduction AS (
        SELECT
            p.log_date,
            SUM(CASE WHEN p.count_type = 'FG' THEN p.count_value ELSE 0 END) AS TotalFG,
            SUM(CASE WHEN p.count_type <> 'FG' THEN p.count_value ELSE 0 END) AS TotalDefects,
            SUM(p.count_value * (60.0 / NULLIF(param.planned_output, 0))) AS TheoreticalMinutes
        FROM dbo.IOT_TOOLBOX_PARTS p
        JOIN dbo.IOT_TOOLBOX_PARAMETER param ON p.line = param.line AND p.model = param.model AND p.part_no = param.part_no
        WHERE p.log_date BETWEEN @StartDate AND @EndDate
            AND (@Line IS NULL OR p.line = @Line)
            AND (@Model IS NULL OR p.model = @Model)
            AND param.planned_output > 0
        GROUP BY p.log_date
    )
    -- 3. Join all aggregated data and calculate final OEE metrics
    SELECT
        d.LogDate AS [date],
        CAST(ISNULL((dp.TotalFG * 100.0) / NULLIF(dp.TotalFG + dp.TotalDefects, 0), 0) AS DECIMAL(5,1)) AS quality,
        CAST(ISNULL(((ISNULL(dpt.PlannedMinutes, 0) - ISNULL(dd.DowntimeMinutes, 0)) * 100.0) / NULLIF(dpt.PlannedMinutes, 0), 0) AS DECIMAL(5,1)) AS availability,
        CAST(ISNULL((dp.TheoreticalMinutes * 100.0) / NULLIF(ISNULL(dpt.PlannedMinutes, 0) - ISNULL(dd.DowntimeMinutes, 0), 0), 0) AS DECIMAL(5,1)) AS performance,
        CAST(ISNULL(
                (((ISNULL(dpt.PlannedMinutes, 0) - ISNULL(dd.DowntimeMinutes, 0)) * 1.0) / NULLIF(dpt.PlannedMinutes, 0)) * ((dp.TheoreticalMinutes * 1.0) / NULLIF(ISNULL(dpt.PlannedMinutes, 0) - ISNULL(dd.DowntimeMinutes, 0), 0)) * ((dp.TotalFG * 1.0) / NULLIF(dp.TotalFG + dp.TotalDefects, 0)) * 100
            , 0) 
        AS DECIMAL(5,1)) AS oee
    FROM @DateSeries d
    LEFT JOIN DailyPlannedTime dpt ON d.LogDate = dpt.LogDate
    LEFT JOIN DailyDowntime dd ON d.LogDate = dd.log_date
    LEFT JOIN DailyProduction dp ON d.LogDate = dp.log_date
    ORDER BY d.LogDate ASC;

END
GO
/****** Object:  StoredProcedure [dbo].[sp_CalculateOEE_PieChart]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE PROCEDURE [dbo].[sp_CalculateOEE_PieChart]
    @StartDate DATE,
    @EndDate DATE,
    @Line VARCHAR(50) = NULL,
    @Model VARCHAR(50) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    -- 1. Calculate Total Planned Time accurately
    --    by identifying the specific shifts that had actual production.
    DECLARE @TotalPlannedMinutes INT;
    
    WITH ActualProductionShifts AS (
        SELECT DISTINCT p.log_date, s.id AS schedule_id
        FROM dbo.IOT_TOOLBOX_PARTS p
        JOIN dbo.LINE_SCHEDULES s ON p.line = s.line AND s.is_active = 1
        WHERE p.log_date BETWEEN @StartDate AND @EndDate
          AND (@Line IS NULL OR p.line = @Line)
          -- Match the production time to the correct shift (handles overnight shifts)
          AND (
               (s.start_time <= s.end_time AND p.log_time BETWEEN s.start_time AND s.end_time) OR
               (s.start_time > s.end_time AND (p.log_time >= s.start_time OR p.log_time < s.end_time))
          )
    )
    SELECT @TotalPlannedMinutes = ISNULL(SUM(
        CASE WHEN s.end_time >= s.start_time THEN DATEDIFF(MINUTE, s.start_time, s.end_time)
             ELSE DATEDIFF(MINUTE, s.start_time, s.end_time) + 1440
        END - s.planned_break_minutes
    ), 0)
    FROM ActualProductionShifts aps
    JOIN dbo.LINE_SCHEDULES s ON aps.schedule_id = s.id;


    -- If there was no production, return all zeros to avoid division by zero errors.
    IF @TotalPlannedMinutes = 0
    BEGIN
        SELECT Quality = 0, Availability = 0, Performance = 0, OEE = 0,
               FG = 0, Defects = 0, NG = 0, Rework = 0, Hold = 0, Scrap = 0, Etc = 0,
               Runtime = 0, PlannedTime = 0, Downtime = 0,
               ActualOutput = 0, TotalTheoreticalMinutes = 0;
        RETURN;
    END

    -- 2. The rest of the calculations are correct as they are already filtered by the date range.
    DECLARE @TotalDowntimeMinutes INT;
    SELECT @TotalDowntimeMinutes = ISNULL(SUM(DATEDIFF(MINUTE, stop_begin, stop_end)), 0)
    FROM dbo.IOT_TOOLBOX_STOP_CAUSES
    WHERE log_date BETWEEN @StartDate AND @EndDate AND (@Line IS NULL OR line = @Line);

    DECLARE @TotalRuntimeMinutes INT;
    SET @TotalRuntimeMinutes = @TotalPlannedMinutes - @TotalDowntimeMinutes;
    IF @TotalRuntimeMinutes < 0 SET @TotalRuntimeMinutes = 0;

    DECLARE @TotalFG INT, @TotalDefects INT, @TotalActualOutput INT, @TotalTheoreticalMinutes DECIMAL(18, 4);
    DECLARE @TotalNG INT, @TotalREWORK INT, @TotalHOLD INT, @TotalSCRAP INT, @TotalETC INT;

    SELECT
        @TotalFG = ISNULL(SUM(CASE WHEN p.count_type = 'FG' THEN p.count_value ELSE 0 END), 0),
        @TotalNG = ISNULL(SUM(CASE WHEN p.count_type = 'NG' THEN p.count_value ELSE 0 END), 0),
        @TotalREWORK = ISNULL(SUM(CASE WHEN p.count_type = 'REWORK' THEN p.count_value ELSE 0 END), 0),
        @TotalHOLD = ISNULL(SUM(CASE WHEN p.count_type = 'HOLD' THEN p.count_value ELSE 0 END), 0),
        @TotalSCRAP = ISNULL(SUM(CASE WHEN p.count_type = 'SCRAP' THEN p.count_value ELSE 0 END), 0),
        @TotalETC = ISNULL(SUM(CASE WHEN p.count_type = 'ETC.' THEN p.count_value ELSE 0 END), 0),
        @TotalTheoreticalMinutes = ISNULL(SUM( p.count_value * (60.0 / NULLIF(param.planned_output, 0)) ), 0)
    FROM dbo.IOT_TOOLBOX_PARTS p
    JOIN dbo.IOT_TOOLBOX_PARAMETER param ON p.line = param.line AND p.model = param.model AND p.part_no = param.part_no
    WHERE p.log_date BETWEEN @StartDate AND @EndDate
        AND (@Line IS NULL OR p.line = @Line)
        AND (@Model IS NULL OR p.model = @Model)
        AND param.planned_output > 0;

    SET @TotalDefects = @TotalNG + @TotalREWORK + @TotalHOLD + @TotalSCRAP + @TotalETC;
    SET @TotalActualOutput = @TotalFG + @TotalDefects;

    -- 3. Final SELECT with accurate calculations
    SELECT
        Quality = CAST(ISNULL((@TotalFG * 100.0) / NULLIF(@TotalActualOutput, 0), 0) AS DECIMAL(5,1)),
        Availability = CAST(ISNULL((@TotalRuntimeMinutes * 100.0) / NULLIF(@TotalPlannedMinutes, 0), 0) AS DECIMAL(5,1)),
        Performance = CAST(ISNULL((@TotalTheoreticalMinutes * 100.0) / NULLIF(@TotalRuntimeMinutes, 0), 0) AS DECIMAL(5,1)),
        OEE = CAST(ISNULL((((@TotalRuntimeMinutes * 1.0) / NULLIF(@TotalPlannedMinutes, 0)) * ((@TotalTheoreticalMinutes * 1.0) / NULLIF(@TotalRuntimeMinutes, 0)) * ((@TotalFG * 1.0) / NULLIF(@TotalActualOutput, 0))) * 100, 0) AS DECIMAL(5,1)),
        FG = @TotalFG,
        Defects = @TotalDefects,
        NG = @TotalNG,
        Rework = @TotalREWORK,
        Hold = @TotalHOLD,
        Scrap = @TotalSCRAP,
        Etc = @TotalETC,
        Runtime = @TotalRuntimeMinutes,
        PlannedTime = @TotalPlannedMinutes,
        Downtime = @TotalDowntimeMinutes,
        ActualOutput = @TotalActualOutput,
        TotalTheoreticalMinutes = CAST(@TotalTheoreticalMinutes AS DECIMAL(18,2));
END
GO
/****** Object:  StoredProcedure [dbo].[sp_DeleteSchedule]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[sp_DeleteSchedule]
    @id INT
AS
BEGIN
    SET NOCOUNT ON;
    DELETE FROM dbo.LINE_SCHEDULES WHERE id = @id;
END
GO
/****** Object:  StoredProcedure [dbo].[sp_GetMissingParameters]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE PROCEDURE [dbo].[sp_GetMissingParameters]
AS
BEGIN
    SET NOCOUNT ON;

    -- Using the optimized query with NOT EXISTS
    ;WITH DistinctPartsToCheck AS (
        SELECT DISTINCT
            line,
            model,
            part_no
        FROM
            dbo.IOT_TOOLBOX_PARTS
    )
    SELECT
        p.line,
        p.model,
        p.part_no
    FROM
        DistinctPartsToCheck p
    WHERE
        NOT EXISTS (
            SELECT 1
            FROM dbo.IOT_TOOLBOX_PARAMETER param
            WHERE
                param.line = p.line
                AND param.model = p.model
                AND param.part_no = p.part_no
        )
    ORDER BY
        p.line, p.model, p.part_no;
END
GO
/****** Object:  StoredProcedure [dbo].[sp_GetSchedules]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE PROCEDURE [dbo].[sp_GetSchedules]
AS
BEGIN
    SET NOCOUNT ON;
    
    SELECT
        id,
        line,
        shift_name,
        -- แปลงรูปแบบเวลาให้เป็น 'HH:MM:SS' (สไตล์ 108)
        CONVERT(VARCHAR(8), start_time, 108) AS start_time,
        CONVERT(VARCHAR(8), end_time, 108) AS end_time,
        planned_break_minutes,
        is_active
    FROM
        dbo.LINE_SCHEDULES
    ORDER BY
        line, shift_name;
END
GO
/****** Object:  StoredProcedure [dbo].[sp_SaveSchedule]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[sp_SaveSchedule]
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
/****** Object:  StoredProcedure [dbo].[sp_UpdatePlannedOutput]    Script Date: 27/06/2025 13:50:07 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[sp_UpdatePlannedOutput]
    @id INT,
    @planned_output INT
AS
BEGIN
    UPDATE IOT_TOOLBOX_PARAMETER
    SET planned_output = @planned_output,
        updated_at = GETDATE()
    WHERE id = @id;
END

GO
USE [master]
GO
ALTER DATABASE [oee_db] SET  READ_WRITE 
GO
